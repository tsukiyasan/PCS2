<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        // 1. 處理日期與篩選
        $dateStartInput = $request->input('date_start', date('Y-m-d'));
        $dateEndInput   = $request->input('date_end', $dateStartInput);
        $dbDateStart = str_replace('-', '', $dateStartInput);
        $dbDateEnd   = str_replace('-', '', $dateEndInput);
        $filters = $request->input('filters', []);

        // 2. 下拉選單線別
        $lines = DB::connection('oracle')
            ->table('NHT.nh_keikaku_no')
            ->where('country_cd', 'TNHT')
            ->whereBetween('tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd])
            ->distinct()
            ->pluck('line');

        // 3. 主查詢：【完全不要 Join 實績表】，只撈計畫與主檔
        $query = DB::connection('oracle')
            ->table('NHT.nh_keikaku_no as keikaku')
            ->select([
                'keikaku.keikaku_no',
                'keikaku.line',
                'keikaku.tonyu_yotei_ymd',
                'keikaku.seihin',
                'keikaku.keikaku_su as tonyu_su',
                'keikaku.moku_ryohin_su as ryohin_su',
                'keikaku.choku',
                'keikaku.order_no',
                'keikaku.keitai_cd',
                'keitai.keitai_ryaku_lang4 as keitai_name',
                'kikaku.sunpo_s',
                'kikaku.sunpo_l',
                'kikaku.itaatsu',
            ])
            ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
            ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
            ->where('keikaku.country_cd', 'TNHT')
            ->whereBetween('keikaku.tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd]);

        // 動態篩選
        $query->when(!empty($filters['line']), function ($q) use ($filters) {
            return $q->where('keikaku.line', $filters['line']);
        });
        $query->when(!empty($filters['keikaku_no']), function ($q) use ($filters) {
            $term = strtoupper(trim($filters['keikaku_no']));
            return $q->where(DB::raw('UPPER(keikaku.keikaku_no)'), 'like', "%{$term}%");
        });
        $query->when(!empty($filters['seihin']), function ($q) use ($filters) {
            $term = strtoupper(trim($filters['seihin']));
            return $q->where(DB::raw('UPPER(keikaku.seihin)'), 'like', "%{$term}%");
        });
        $query->when(!empty($filters['order_no']), function ($q) use ($filters) {
            $term = strtoupper(trim($filters['order_no']));
            return $q->where(DB::raw('UPPER(keikaku.order_no)'), 'like', "%{$term}%");
        });

        // 4. 執行分頁 (讓 Yajra 安全地去解析最單純的 SQL)
        $plans = $query
            ->orderBy('keikaku.tonyu_yotei_ymd', 'ASC')
            ->orderBy('keikaku.line', 'ASC')
            ->orderBy('keikaku.keikaku_no', 'ASC')
            ->paginate(15)
            ->appends($request->all());

        // ★★★ 5. 破局點：在 PHP 端組合實績資料 ★★★
        // 抓出「當前這 15 筆」計畫的編號
        $keikakuNos = collect($plans->items())->pluck('keikaku_no')->map(function($k) {
            return trim($k);
        })->filter()->toArray();

        if (!empty($keikakuNos)) {
            // 另外下一次輕量級的查詢，只抓這 15 筆的實績
            $actualSums = DB::connection('oracle')
                ->table('NHT.NH_SETSUDAN_MENTORI')
                ->select([
                    DB::raw('TRIM(KEIKAKU_NO) as keikaku_no'),
                    DB::raw('SUM(SETSUDAN_SU) as total_setsudan')
                ])
                ->where('COUNTRY_CD', 'TNHT')
                ->whereIn(DB::raw('TRIM(KEIKAKU_NO)'), $keikakuNos)
                // 不限制 SAGYO_YMD，確保抓到所有跨天實績
                ->groupBy(DB::raw('TRIM(KEIKAKU_NO)'))
                ->get()
                ->keyBy('keikaku_no'); // 轉成以編號為 Key 的 Collection

            // 將實績塞回分頁結果中
            foreach ($plans->items() as $plan) {
                $kNo = trim($plan->keikaku_no);
                if ($actualSums->has($kNo)) {
                    $plan->total_setsudan = $actualSums[$kNo]->total_setsudan;
                } else {
                    $plan->total_setsudan = 0; // 沒實績就給 0
                }
            }
        } else {
            // 防呆處理，如果完全沒資料
            foreach ($plans->items() as $plan) {
                $plan->total_setsudan = 0;
            }
        }

        // 6. 回傳視圖
        return view('production_plan', [
            'plans'     => $plans,
            'lines'     => $lines,
            'dateStart' => $dateStartInput,
            'dateEnd'   => $dateEndInput,
            'filters'   => $filters,
        ]);
    }
}