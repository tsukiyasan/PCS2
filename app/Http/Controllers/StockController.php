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
        // 1. 取得畫面上選擇的篩選條件與日期
        // 如果使用者沒輸入，預設為「當月第一天」與「當月最後一天」(格式：YYYY-MM-DD)
        $dateStartInput = $request->get('date_start', now()->startOfMonth()->format('Y-m-d'));
        $dateEndInput   = $request->get('date_end', now()->endOfMonth()->format('Y-m-d'));      
          
        // 🌟 接收畫面的過濾條件陣列 (預設為空陣列)
        $filters = $request->get('filters', []);

        // 轉換為資料庫格式 (YYYYMMDD)
        $start = Carbon::parse($dateStartInput)->format('Ymd');
        $end   = Carbon::parse($dateEndInput)->format('Ymd');
        $yymm  = Carbon::parse($dateEndInput)->format('Ym');

        // 2. 建立內層核心加總的子查詢 (Subquery)
        $subquery = DB::connection('oracle')->table('nh_seihin_ukeharai s')
            ->select([
                DB::raw('TRIM(s.seihin) AS seihin'),
                DB::raw('MAX(s.ukeharai_ymd) AS latest_ukeharai_ymd'),
                DB::raw('MIN(SUBSTR(s.konpo_konpo_ymd, 1, 8)) AS min_konpo_ymd'),
                DB::raw('SUM(s.gesho_zan + s.kotei_ukeire_su + s.henpin_ukeire_su + s.furi_ukeire_su + s.hason_ukeire_su + s.gaichu_ukeire_su 
                            - s.ryohin_harai_su - s.hason_harai_su - s.furi_harai_su - s.hoju_harai_su 
                            + s.hosei_su - s.daino_su - s.zaiso_su - s.sample_su) AS total_maisu')
            ])
            ->whereIn('s.kotei_cd', ['11','15','17','19','20','22','S01','S02','S03'])
            ->where(DB::raw('TRIM(s.country_cd)'), 'TNHT')
            ->where('s.ukeharai_ymd', '>=', $start)
            ->where('s.ukeharai_ymd', '<=', $end)
            ->groupBy(DB::raw('TRIM(s.seihin)'));

        // 3. 建立主查詢實例
        $mainQuery = DB::connection('oracle')->query()
            ->select([
                'yoto.yoto_name AS yoto_name',
                'summary.seihin AS seihin',
                'kikaku.shoshu_cd AS customer_name',
                'summary.min_konpo_ymd AS konbao_ymd',
                'summary.total_maisu AS maisu',
                'summary.latest_ukeharai_ymd AS ukeharai_ymd'
            ])
            ->fromSub($subquery, 'summary')
            ->leftJoin('nh_kikakusho_mst kikaku', function ($join) {
                $join->on('summary.seihin', '=', DB::raw('TRIM(kikaku.seihin)'));
            })
            ->leftJoin('nh_yoto_mst yoto', function ($join) {
                $join->on(DB::raw('TRIM(kikaku.yoto)'), '=', DB::raw('TRIM(yoto.yoto_cd)'));
            })
            ->where('summary.total_maisu', '<>', 0);

        // ==========================================
        // 🌟 核心新增：動態組合前端傳來的過濾條件
        // ==========================================
        
        // [用途]：下拉選單通常是精確比對
        if (!empty($filters['yoto'])) {
            $mainQuery->where(DB::raw('TRIM(yoto.yoto_name)'), '=', trim($filters['yoto']));
        }

        // [製品]：文字輸入框，使用 LIKE 模糊搜尋，並強制轉大寫比對避免大小寫搜不到
        if (!empty($filters['seihin'])) {
            $mainQuery->where(DB::raw('UPPER(summary.seihin)'), 'LIKE', '%' . strtoupper(trim($filters['seihin'])) . '%');
        }

        // [客戶別]：文字輸入框，同樣使用 LIKE 模糊搜尋與轉大寫
        if (!empty($filters['customer'])) {
            $mainQuery->where(DB::raw('UPPER(kikaku.shoshu_cd)'), 'LIKE', '%' . strtoupper(trim($filters['customer'])) . '%');
        }

        // ==========================================
        
        // 執行查詢並排序
        $dbData = $mainQuery->orderBy('summary.seihin', 'asc')->get()->all();

        // ==========================================
        // 🌟 核心新增：建立廠商與吋法的對照字典
        // ==========================================
        $productMapping = [
            '2SHFB0D0AACM' => ['vendor' => 'INX',    'size' => '1100*1300'],
            '2SGFB0D0AACM' => ['vendor' => 'INX',    'size' => '1100*1300'],
            '2SHAB0D0BQS'  => ['vendor' => 'INX',    'size' => '1100*1300'],
            '2SGAB0D0BQS'  => ['vendor' => 'INX',    'size' => '1100*1300'],
            '2SHFB0D0AAAU' => ['vendor' => 'AUO',    'size' => '1100*1300'],
            '2SHAB0D0BQAU' => ['vendor' => 'AUO',    'size' => '1100*1300'],
            '2SMFB0D0AAAU' => ['vendor' => 'AUO',    'size' => '1100*1300'],
            '2SMAB0D0BQAU' => ['vendor' => 'AUO',    'size' => '1100*1300'],
            '2SHA6888BQCM' => ['vendor' => 'GP',     'size' => '680*880'],
            '2SMA6888BQCM' => ['vendor' => 'GP',     'size' => '680*880'],
            '2SHWB0D0EFBE' => ['vendor' => 'BOE',    'size' => '1100*1300'],
            '2SHEB0D0EABE' => ['vendor' => 'BOE',    'size' => '1100*1300'],
            '2SMAB0C5EADW' => ['vendor' => 'DWFC',   'size' => '1100x1250'],
            '2SHFB0D0AAIO' => ['vendor' => 'IVO',    'size' => '1100*1300'],
            '2SMAB0D0BQIO' => ['vendor' => 'IVO',    'size' => '1100*1300'],
            '2SGFB0D0AAIA' => ['vendor' => 'INESA',  'size' => '1100*1300'],
            '2SGAB0D0BQIA' => ['vendor' => 'INESA',  'size' => '1100*1300'],
            '2SGFB0D0AALA' => ['vendor' => 'LAIBAO', 'size' => '1100*1300'],
            '2SGAB0D0BQLA' => ['vendor' => 'LAIBAO', 'size' => '1100*1300'],
            '2SHFC0D0CRHS' => ['vendor' => 'HSD',    'size' => '1200*1300'],
            '2SMEB0D0EABE' => ['vendor' => 'BOE',    'size' => '1100*1300'],
        ];
        // 4. 用 PHP 幫每一筆資料動態塞入 nengetsu (年月)、廠商、吋法欄位
        foreach ($dbData as $row) {
            $row->nengetsu = $yymm;

            // 查表對照：若製品代碼吻合，就注入廠商與吋法，否則預設顯示 '-'
            $mappedData = $productMapping[$row->seihin] ?? ['vendor' => '-', 'size' => '-'];
            $row->vendor = $mappedData['vendor'];
            $row->size   = $mappedData['size'];
        }

        // 5. 將資料轉為 Collection 物件
        $collection = collect($dbData);

        // 6. 手動製作 Paginator 分頁物件
        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $stocks = new LengthAwarePaginator(
            $currentItems, 
            $collection->count(), 
            $perPage, 
            $currentPage, 
            // 🌟 確保分頁的網址會帶上過濾條件，換頁時條件才不會消失
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        // 7. 🌟 動態抓取「該日期區間內」實際有庫存紀錄的用途名稱
        $yotoOptions = DB::connection('oracle')
            ->table('nh_seihin_ukeharai s') // 從每日庫存表出發
            // 關聯規格書取得 yoto_cd
            ->leftJoin('nh_kikakusho_mst kikaku', function ($join) {
                $join->on(DB::raw('TRIM(s.seihin)'), '=', DB::raw('TRIM(kikaku.seihin)'));
            })
            // 關聯用途主檔取得 yoto_name
            ->leftJoin('nh_yoto_mst yoto', function ($join) {
                $join->on(DB::raw('TRIM(kikaku.yoto)'), '=', DB::raw('TRIM(yoto.yoto_cd)'));
            })
            // 套用與主查詢一模一樣的過濾條件與「日期區間」
            ->whereIn('s.kotei_cd', ['11','15','17','19','20','22','S01','S02','S03'])
            ->where(DB::raw('TRIM(s.country_cd)'), 'TNHT')
            ->where('s.ukeharai_ymd', '>=', $start)
            ->where('s.ukeharai_ymd', '<=', $end)
            // 確保有抓到名稱才列入選項
            ->whereNotNull('yoto.yoto_name') 
            // 只抓取不重複的用途名稱
            ->select(DB::raw('TRIM(yoto.yoto_name) as yoto_name'))
            ->distinct()
            ->orderBy('yoto_name', 'asc')
            ->pluck('yoto_name')
            ->toArray(); // 轉回純陣列供 Blade 使用

        // 8. 回傳至 stock.blade.php
        return view('stock', [
            'stocks'      => $stocks,
            'yotoOptions' => $yotoOptions,
            'dateStart'   => $dateStartInput,
            'dateEnd'     => $dateEndInput,
            'filters'     => $filters,
        ]);
    }
}