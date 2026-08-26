<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockController extends Controller
{
    public function index(Request $request)
    {
        // ============================================================
        // 1. 取得畫面上選擇的篩選條件與日期
        // ============================================================

        // 如果使用者沒輸入，預設為當月第一天與當月最後一天
        $dateStartInput = $request->get(
            'date_start',
            now()->startOfMonth()->format('Y-m-d')
        );

        $dateEndInput = $request->get(
            'date_end',
            now()->endOfMonth()->format('Y-m-d')
        );

        // 接收畫面的過濾條件陣列
        $filters = $request->get('filters', []);

        // ============================================================
        // ★ 修改處 1
        // 日期轉換
        // ============================================================

        // 轉換為 Oracle 資料庫使用的 YYYYMMDD
        //
        // 例如：
        // 2026-08-31
        // ↓
        // 20260831
        //
        $start = Carbon::parse($dateStartInput)->format('Ymd');
        $end   = Carbon::parse($dateEndInput)->format('Ymd');

        // 畫面顯示年月，例如 202608
        $yymm = Carbon::parse($dateEndInput)->format('Ym');


        // ============================================================
        // 2. 建立內層核心加總的子查詢
        // ============================================================

        $subquery = DB::connection('oracle')
            ->table('nh_seihin_ukeharai s')
            ->select([

                // ====================================================
                // 製品
                // ====================================================

                DB::raw(
                    'TRIM(s.seihin) AS seihin'
                ),


                // ====================================================
                // 查詢日期區間內最後的受払日期
                // ====================================================

                DB::raw(
                    'MAX(s.ukeharai_ymd) AS latest_ukeharai_ymd'
                ),


                // ====================================================
                // ★ 修改處 2：konbao_ymd
                // ====================================================
                //
                // konbao_ymd 的規則：
                //
                // 「找最早的 konpo_konpo_ymd」
                //
                // 原本：
                //
                // MIN(SUBSTR(s.konpo_konpo_ymd, 1, 8))
                //
                // 現在增加 TRIM：
                //
                // MIN(
                //     TRIM(
                //         SUBSTR(
                //             s.konpo_konpo_ymd,
                //             1,
                //             8
                //         )
                //     )
                // )
                //
                // 這樣可以避免 konpo_konpo_ymd
                // 前後存在空白造成問題。
                //
                // ====================================================

                DB::raw("
                    MIN(
                        TRIM(
                            SUBSTR(
                                s.konpo_konpo_ymd,
                                1,
                                8
                            )
                        )
                    ) AS min_konpo_ymd
                "),


                // ====================================================
                // 庫存數量
                // ====================================================

                DB::raw("
                    SUM(
                          s.gesho_zan
                        + s.kotei_ukeire_su
                        + s.henpin_ukeire_su
                        + s.furi_ukeire_su
                        + s.hason_ukeire_su
                        + s.gaichu_ukeire_su
                        - s.ryohin_harai_su
                        - s.hason_harai_su
                        - s.furi_harai_su
                        - s.hoju_harai_su
                        + s.hosei_su
                        - s.daino_su
                        - s.zaiso_su
                        - s.sample_su
                    ) AS total_maisu
                ")
            ])

            // ========================================================
            // 製程條件
            // ========================================================

            ->whereIn('s.kotei_cd', [
                '11',
                '15',
                '17',
                '19',
                '20',
                '22',
                'S01',
                'S02',
                'S03'
            ])

            // ========================================================
            // 國別
            // ========================================================

            ->where(
                DB::raw('TRIM(s.country_cd)'),
                'TNHT'
            )

            // ========================================================
            // 日期條件
            // ========================================================
            //
            // 例如查：
            //
            // 2026-08-31 ～ 2026-08-31
            //
            // 會變成：
            //
            // s.ukeharai_ymd >= '20260831'
            // AND
            // s.ukeharai_ymd <= '20260831'
            //
            // ========================================================

            ->where(
                's.ukeharai_ymd',
                '>=',
                $start
            )

            ->where(
                's.ukeharai_ymd',
                '<=',
                $end
            )

            // ========================================================
            // 一個製品一筆
            // ========================================================

            ->groupBy(
                DB::raw('TRIM(s.seihin)')
            );


        // ============================================================
        // 3. 建立主查詢
        // ============================================================

        $mainQuery = DB::connection('oracle')
            ->query()

            ->select([

                // 用途名稱
                'yoto.yoto_name AS yoto_name',

                // 製品
                'summary.seihin AS seihin',

                // Customer
                'kikaku.shoshu_cd AS customer_name',

                // ====================================================
                // ★ 修改處 3
                // ====================================================
                //
                // 從 Subquery 取得最早 konpo_konpo_ymd
                //
                // Subquery：
                //
                // MIN(...) AS min_konpo_ymd
                //
                // Main Query：
                //
                // summary.min_konpo_ymd AS konbao_ymd
                //
                // ====================================================

                'summary.min_konpo_ymd AS konbao_ymd',

                // 庫存數量
                'summary.total_maisu AS maisu',

                // 最新受払日期
                'summary.latest_ukeharai_ymd AS ukeharai_ymd'
            ])

            ->fromSub(
                $subquery,
                'summary'
            )


            // ========================================================
            // 關聯規格書
            // ========================================================

            ->leftJoin(
                'nh_kikakusho_mst kikaku',
                function ($join) {

                    $join->on(
                        'summary.seihin',
                        '=',
                        DB::raw(
                            'TRIM(kikaku.seihin)'
                        )
                    );
                }
            )


            // ========================================================
            // 關聯用途主檔
            // ========================================================

            ->leftJoin(
                'nh_yoto_mst yoto',
                function ($join) {

                    $join->on(
                        DB::raw(
                            'TRIM(kikaku.yoto)'
                        ),
                        '=',
                        DB::raw(
                            'TRIM(yoto.yoto_cd)'
                        )
                    );
                }
            )


            // ========================================================
            // 只顯示庫存不等於 0
            // ========================================================

            ->where(
                'summary.total_maisu',
                '<>',
                0
            );


        // ============================================================
        // 4. 動態組合前端過濾條件
        // ============================================================

        // ------------------------------------------------------------
        // 用途
        // ------------------------------------------------------------

        if (!empty($filters['yoto'])) {

            $mainQuery->where(
                DB::raw(
                    'TRIM(yoto.yoto_name)'
                ),
                '=',
                trim(
                    $filters['yoto']
                )
            );
        }


        // ------------------------------------------------------------
        // 製品
        // ------------------------------------------------------------

        if (!empty($filters['seihin'])) {

            $mainQuery->where(
                DB::raw(
                    'UPPER(summary.seihin)'
                ),
                'LIKE',
                '%' .
                strtoupper(
                    trim(
                        $filters['seihin']
                    )
                ) .
                '%'
            );
        }


        // ------------------------------------------------------------
        // Customer
        // ------------------------------------------------------------

        if (!empty($filters['customer'])) {

            $mainQuery->where(
                DB::raw(
                    'UPPER(kikaku.shoshu_cd)'
                ),
                'LIKE',
                '%' .
                strtoupper(
                    trim(
                        $filters['customer']
                    )
                ) .
                '%'
            );
        }


        // ============================================================
        // 5. 執行查詢並排序
        // ============================================================

        $dbData = $mainQuery
            ->orderBy(
                'summary.seihin',
                'asc'
            )
            ->get()
            ->all();


        // ============================================================
        // 6. 建立廠商與尺寸對照表
        // ============================================================

        $productMapping = [

            '2SHFB0D0AACM' => [
                'vendor' => 'INX',
                'size'   => '1100*1300'
            ],

            '2SGFB0D0AACM' => [
                'vendor' => 'INX',
                'size'   => '1100*1300'
            ],

            '2SHAB0D0BQS' => [
                'vendor' => 'INX',
                'size'   => '1100*1300'
            ],

            '2SGAB0D0BQS' => [
                'vendor' => 'INX',
                'size'   => '1100*1300'
            ],

            '2SHFB0D0AAAU' => [
                'vendor' => 'AUO',
                'size'   => '1100*1300'
            ],

            '2SHAB0D0BQAU' => [
                'vendor' => 'AUO',
                'size'   => '1100*1300'
            ],

            '2SMFB0D0AAAU' => [
                'vendor' => 'AUO',
                'size'   => '1100*1300'
            ],

            '2SMAB0D0BQAU' => [
                'vendor' => 'AUO',
                'size'   => '1100*1300'
            ],

            '2SHA6888BQCM' => [
                'vendor' => 'GP',
                'size'   => '680*880'
            ],

            '2SMA6888BQCM' => [
                'vendor' => 'GP',
                'size'   => '680*880'
            ],

            '2SHWB0D0EFBE' => [
                'vendor' => 'BOE',
                'size'   => '1100*1300'
            ],

            '2SHEB0D0EABE' => [
                'vendor' => 'BOE',
                'size'   => '1100*1300'
            ],

            '2SMAB0C5EADW' => [
                'vendor' => 'DWFC',
                'size'   => '1100x1250'
            ],

            '2SHFB0D0AAIO' => [
                'vendor' => 'IVO',
                'size'   => '1100*1300'
            ],

            '2SMAB0D0BQIO' => [
                'vendor' => 'IVO',
                'size'   => '1100*1300'
            ],

            '2SGFB0D0AAIA' => [
                'vendor' => 'INESA',
                'size'   => '1100*1300'
            ],

            '2SGAB0D0BQIA' => [
                'vendor' => 'INESA',
                'size'   => '1100*1300'
            ],

            '2SGFB0D0AALA' => [
                'vendor' => 'LAIBAO',
                'size'   => '1100*1300'
            ],

            '2SGAB0D0BQLA' => [
                'vendor' => 'LAIBAO',
                'size'   => '1100*1300'
            ],

            '2SHFC0D0CRHS' => [
                'vendor' => 'HSD',
                'size'   => '1200*1300'
            ],

            '2SMEB0D0EABE' => [
                'vendor' => 'BOE',
                'size'   => '1100*1300'
            ],
        ];


        // ============================================================
        // 7. 填入年月、廠商、尺寸
        // ============================================================

        foreach ($dbData as $row) {

            $row->nengetsu = $yymm;

            $seihinKey = trim(
                $row->seihin
            );

            $mappedData =
                $productMapping[$seihinKey]
                ?? [
                    'vendor' => '-',
                    'size'   => '-'
                ];

            $row->vendor = $mappedData['vendor'];
            $row->size   = $mappedData['size'];
        }


        // ============================================================
        // 8. 動態收集廠商選項
        // ============================================================

        $allVendorOptions = [];

        foreach ($dbData as $item) {

            if (
                !empty($item->vendor)
                && $item->vendor !== '-'
            ) {

                $allVendorOptions[] =
                    $item->vendor;
            }
        }

        $vendorOptions =
            array_values(
                array_unique(
                    $allVendorOptions
                )
            );

        sort($vendorOptions);


        // ============================================================
        // 9. 廠商過濾
        // ============================================================

        if (!empty($filters['vendor'])) {

            $dbData = array_filter(
                $dbData,
                function ($row) use ($filters) {

                    return trim(
                        $row->vendor
                    ) === trim(
                        $filters['vendor']
                    );
                }
            );
        }


        // ============================================================
        // 10. Collection
        // ============================================================

        $collection = collect(
            $dbData
        );


        // ============================================================
        // 11. 分頁
        // ============================================================

        $perPage = 15;

        $currentPage =
            LengthAwarePaginator::resolveCurrentPage();

        $currentItems =
            $collection
                ->slice(
                    ($currentPage - 1) * $perPage,
                    $perPage
                )
                ->values();

        $stocks = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'path' =>
                    LengthAwarePaginator::resolveCurrentPath(),

                'query' =>
                    $request->query()
            ]
        );


        // ============================================================
        // 12. 動態抓取日期區間內實際存在的用途
        // ============================================================

        $yotoOptions = DB::connection('oracle')
            ->table(
                'nh_seihin_ukeharai s'
            )

            ->leftJoin(
                'nh_kikakusho_mst kikaku',
                function ($join) {

                    $join->on(
                        DB::raw(
                            'TRIM(s.seihin)'
                        ),
                        '=',
                        DB::raw(
                            'TRIM(kikaku.seihin)'
                        )
                    );
                }
            )

            ->leftJoin(
                'nh_yoto_mst yoto',
                function ($join) {

                    $join->on(
                        DB::raw(
                            'TRIM(kikaku.yoto)'
                        ),
                        '=',
                        DB::raw(
                            'TRIM(yoto.yoto_cd)'
                        )
                    );
                }
            )

            ->whereIn(
                's.kotei_cd',
                [
                    '11',
                    '15',
                    '17',
                    '19',
                    '20',
                    '22',
                    'S01',
                    'S02',
                    'S03'
                ]
            )

            ->where(
                DB::raw(
                    'TRIM(s.country_cd)'
                ),
                'TNHT'
            )

            ->where(
                's.ukeharai_ymd',
                '>=',
                $start
            )

            ->where(
                's.ukeharai_ymd',
                '<=',
                $end
            )

            ->whereNotNull(
                'yoto.yoto_name'
            )

            ->select(
                DB::raw(
                    'TRIM(yoto.yoto_name) AS yoto_name'
                )
            )

            ->distinct()

            ->orderBy(
                'yoto_name',
                'asc'
            )

            ->pluck(
                'yoto_name'
            )

            ->toArray();


        // ============================================================
        // 13. 回傳 View
        // ============================================================

        return view(
            'stock',
            [
                'stocks' =>
                    $stocks,

                'yotoOptions' =>
                    $yotoOptions,

                'vendorOptions' =>
                    $vendorOptions,

                'dateStart' =>
                    $dateStartInput,

                'dateEnd' =>
                    $dateEndInput,

                'filters' =>
                    $filters,
            ]
        );
    }
}