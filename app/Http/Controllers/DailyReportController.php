<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. 處理日期
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

        // 3. 建立子查詢 A: 切斷實績 (不設日期限制，抓取該計畫所有累計實績)
        $subQuery = DB::connection('oracle')
            ->table('NHT.NH_SETSUDAN_MENTORI as nh_sm')
            ->select([
                'nh_sm.keikaku_no',
                DB::raw('SUM(nh_sm.setsudan_su) as total_setsudan')
            ])
            ->where('nh_sm.country_cd', 'TNHT')
            ->groupBy('nh_sm.keikaku_no');

        // 建立子查詢 B: 檢查良品數 (根據篩選日期抓取 K19 處理實績)
        $kensaSubQuery = DB::connection('oracle')
            ->table('NHT.nh_kensa as k') // 建議加上 Schema NHT 如果需要
            ->select([
                'k.keikaku_no',
                DB::raw('SUM(k.ryohin_su) as total_ryohin')
            ])
            ->where('k.kensa_shurui', 'K19') 
            ->whereBetween('k.sagyo_ymd', [$dbDateStart, $dbDateEnd])
            ->groupBy('k.keikaku_no');

        // 4. 主查詢
        $query = DB::connection('oracle')
            ->table('NHT.nh_keikaku_no as keikaku')
            ->select([
                'keikaku.keikaku_no',
                'keikaku.line',
                'keikaku.tonyu_yotei_ymd',
                'keikaku.seihin',
                'keikaku.keikaku_su as tonyu_su',     // 投入計畫數
                'keikaku.moku_ryohin_su as moku_su', // 目標良品數
                'keikaku.choku',
                'keikaku.order_no',
                'keikaku.keitai_cd',
                'keitai.keitai_ryaku_lang4 as keitai_name',
                'kikaku.sunpo_s',
                'kikaku.sunpo_l',
                'kikaku.itaatsu',
                // 實績欄位處理 (使用 NVL 避免 null 導致計算出錯)
                DB::raw('NVL(sm_sum.total_setsudan, 0) as total_setsudan'),
                DB::raw('NVL(k_sum.total_ryohin, 0) as total_ryohin'),
                // 計算達成率 (良品實績 / 目標良品數)
                DB::raw('CASE WHEN keikaku.moku_ryohin_su > 0 
                              THEN ROUND(NVL(k_sum.total_ryohin, 0) / keikaku.moku_ryohin_su * 100, 2) 
                              ELSE 0 END as achieve_rate'),
                // 計算良率 (良品實績 / 切斷總數)
                DB::raw('CASE WHEN NVL(sm_sum.total_setsudan, 0) > 0 
                              THEN ROUND(NVL(k_sum.total_ryohin, 0) / sm_sum.total_setsudan * 100, 2) 
                              ELSE 0 END as yield_rate')
            ])
            ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
            ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
            
            // Join A: 切斷子查詢
            ->leftJoinSub($subQuery, 'sm_sum', function ($join) {
                $join->on('keikaku.keikaku_no', '=', 'sm_sum.keikaku_no');
            })

            // Join B: 良品子查詢 (新加入)
            ->leftJoinSub($kensaSubQuery, 'k_sum', function ($join) {
                $join->on('keikaku.keikaku_no', '=', 'k_sum.keikaku_no');
            })
            
            ->where('keikaku.country_cd', 'TNHT')
            ->whereBetween('keikaku.tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd]);

        // 5. 動態篩選 (保持原樣)
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

        // 6. 排序與分頁
        $plans = $query
            ->orderBy('keikaku.tonyu_yotei_ymd', 'ASC')
            ->orderBy('keikaku.line', 'ASC')
            ->orderBy('keikaku.keikaku_no', 'ASC')
            ->paginate(15)
            ->appends($request->all());

        return view('production_daily_report', [
            'plans'     => $plans,
            'lines'     => $lines,
            'dateStart' => $dateStartInput,
            'dateEnd'   => $dateEndInput,
            'filters'   => $filters,
        ]);
    }
}