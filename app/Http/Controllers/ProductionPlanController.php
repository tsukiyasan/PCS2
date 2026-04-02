<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        // 1. 處理日期與基本過濾器
        $dateStartInput = $request->input('date_start', date('Y-m-d'));
        $dateEndInput   = $request->input('date_end', $dateStartInput);
        $dbDateStart = str_replace('-', '', $dateStartInput);
        $dbDateEnd   = str_replace('-', '', $dateEndInput);
        $filters = $request->input('filters', []);

        // 2. 獲取線別下拉選單
        $lines = DB::connection('oracle')
            ->table('NHT.nh_keikaku_no')
            ->where('country_cd', 'TNHT')
            ->whereBetween('tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd])
            ->distinct()
            ->pluck('line');

        // 3. 主查詢：改用 DB::raw 注入子查詢
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
                'sm_sum.TOTAL_SETSUDAN' // 從原生子查詢獲取的欄位
            ])
            ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
            ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
            
            // --- 關鍵修正：改用原生子查詢語法 ---
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
                // 使用 TRIM 確保主表與子查詢的編號即使有空白也能對上
                $join->on(DB::raw('TRIM(keikaku.keikaku_no)'), '=', DB::raw('TRIM(sm_sum.KEIKAKU_NO)'));
            })
            
            ->where('keikaku.country_cd', 'TNHT')
            ->whereBetween('keikaku.tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd]);

        // 4