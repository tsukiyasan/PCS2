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
        // 建立子查詢 C: 在製品轉移實績 (振替)
        $furikaeSubQuery = DB::connection('oracle')
            ->table('NHT.nh_shikakari_furikae as furikae')
            ->select([
                'furikae.ato_keikaku_no', // 這裡用 ato_keikaku_no 作為 Join 鍵，代表轉入該計畫的實績
                DB::raw('SUM(furikae.total_maisu) as total_furikae')
            ])
            ->where('furikae.country_cd', 'TNHT') // 根據 masterCountry 篩選
            ->whereBetween('furikae.furikae_ymd', [$dbDateStart, $dbDateEnd]) // 鎖定 20260413
            ->groupBy('furikae.ato_keikaku_no');
        // 4. 主查詢整合 (完全保留你原先的所有欄位，僅在末尾新增整合項)
        $query = DB::connection('oracle')
            ->table('NHT.nh_keikaku_no as keikaku')
            ->select([
                'keikaku.keikaku_no',
                'keikaku.line', // 保留原位
                'keikaku.tonyu_yotei_ymd',
                'keikaku.seihin',
                'keikaku.keikaku_su as tonyu_su',
                'keikaku.moku_ryohin_su as ryohin_su', // 這是你原先定義的別名
                'keikaku.choku',
                'keikaku.order_no',
                'keikaku.keitai_cd',
                'keitai.keitai_ryaku_lang4 as keitai_name',
                'kikaku.sunpo_s',
                'kikaku.sunpo_l',
                'kikaku.itaatsu',
                DB::raw('NVL(sm_sum.total_setsudan, 0) as total_setsudan'),
                // --- 以下為整合新增，不更動上方原有的 select 內容 ---
                DB::raw('NVL(k_sum.total_ryohin, 0) as total_ryohin'),
                DB::raw('NVL(f_sum.total_furikae, 0) as total_furikae'),
                // 計算綜合達成數 (良品數 + 轉移數)
                DB::raw('(NVL(k_sum.total_ryohin, 0) + NVL(f_sum.total_furikae, 0)) as total_actual_output')
            ])
            ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
            ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
            
            // Join A: 切斷子查詢
            ->leftJoinSub($subQuery, 'sm_sum', function ($join) {
                $join->on('keikaku.keikaku_no', '=', 'sm_sum.keikaku_no');
            })

            // Join B: 良品子查詢 (K19)
            ->leftJoinSub($kensaSubQuery, 'k_sum', function ($join) {
                $join->on('keikaku.keikaku_no', '=', 'k_sum.keikaku_no');
            })

            // Join C: 振替子查詢 (整合新增)
            ->leftJoinSub($furikaeSubQuery, 'f_sum', function ($join) {
                $join->on('keikaku.keikaku_no', '=', 'f_sum.ato_keikaku_no');
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