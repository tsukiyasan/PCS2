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

    // 3. 建立子查詢 (完全對應你提供的 SQL 子查詢部分)
    $subQuery = DB::connection('oracle')
        ->table('NHT.NH_SETSUDAN_MENTORI as nh_sm')
        ->select([
            'nh_sm.KEIKAKU_NO',
            DB::raw('SUM(nh_sm.SETSUDAN_SU) AS TOTAL_SETSUDAN')
        ])
        ->where('nh_sm.COUNTRY_CD', 'TNHT')
        ->whereBetween('nh_sm.SAGYO_YMD', [$dbDateStart, $dbDateEnd])
        ->groupBy('nh_sm.KEIKAKU_NO');

    // 4. 主查詢
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
            'sm_sum.TOTAL_SETSUDAN' // 確保子查詢的欄位被選取
        ])
        ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
        ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
        // 對應 SQL 的 LEFT JOIN (SELECT ...) sm_sum ON ...
        ->leftJoinSub($subQuery, 'sm_sum', function ($join) {
            $join->on('keikaku.keikaku_no', '=', 'sm_sum.KEIKAKU_NO');
        })
        ->where('keikaku.country_cd', 'TNHT')
        ->whereBetween('keikaku.tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd]);

    // 5. 動態篩選 (略...)

    // 6. 執行分頁
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
}}