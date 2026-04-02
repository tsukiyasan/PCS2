<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
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

        // 3. 建構查詢：直接使用 DB::raw 注入原生 SQL 的 Left Join 結構
        $query = DB::connection('oracle')
            ->table('NHT.nh_keikaku_no as keikaku')
            ->select([
                'keikaku.keikaku_no',
                'keikaku.line',
                'keikaku.tonyu_yotei_ymd',
                'keikaku.seihin',
                'keikaku.keikaku_su AS tonyu_su',
                'keikaku.moku_ryohin_su AS ryohin_su',
                'keikaku.choku',
                'keikaku.order_no',
                'keikaku.keitai_cd',
                'keitai.keitai_ryaku_lang4 AS keitai_name',
                'kikaku.sunpo_s',
                'kikaku.sunpo_l',
                'kikaku.itaatsu',
                'sm_sum.TOTAL_SETSUDAN' // 引用子查詢欄位
            ])
            ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
            ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
            
            // --- 核心修正：原生 SQL 注入，確保與資料庫工具跑出的語法一致 ---
            ->leftJoin(DB::raw("(
                SELECT 
                    nh_sm.KEIKAKU_NO, 
                    SUM(nh_sm.SETSUDAN_SU) AS TOTAL_SETSUDAN
                FROM 
                    NHT.NH_SETSUDAN_MENTORI nh_sm
                WHERE 
                    nh_sm.COUNTRY_CD = 'TNHT' 
                    AND nh_sm.SAGYO_YMD BETWEEN '$dbDateStart' AND '$dbDateEnd'
                GROUP BY 
                    nh_sm.KEIKAKU_NO
            ) sm_sum"), function($join) {
                // 使用 TRIM 處理 Oracle VARCHAR2 結尾可能存在的空白
                $join->on(DB::raw('TRIM(keikaku.keikaku_no)'), '=', DB::raw('TRIM(sm_sum.KEIKAKU_NO)'));
            })
            
            ->where('keikaku.country_cd', 'TNHT')
            ->whereBetween('keikaku.tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd]);

        // 4. 動態篩選
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

        // 5. 排序與分頁
        $plans = $query
            ->orderBy('keikaku.tonyu_yotei_ymd', 'ASC')
            ->orderBy('keikaku.line', 'ASC')
            ->orderBy('keikaku.keikaku_no', 'ASC')
            ->paginate(15)
            ->appends($request->all());

        return view('production_plan', [
            'plans'     => $plans,
            'lines'     => $lines,
            'dateStart' => $dateStartInput,
            'dateEnd'   => $dateEndInput,
            'filters'   => $filters,
        ]);
    }
}