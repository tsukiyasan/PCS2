<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        // 1. 處理日期 (預設為當天)
        // 前端 input type="date" 傳來的是 "2023-10-27"，需轉為 "20231027" 格式給 Oracle
        $dateStartInput = $request->input('date_start', date('Y-m-d'));
        $dateEndInput   = $request->input('date_end', $dateStartInput); // 預設結束日等於開始日

        $dbDateStart = str_replace('-', '', $dateStartInput);
        $dbDateEnd   = str_replace('-', '', $dateEndInput);

        // 2. 接收表格內的篩選條件 (陣列形式 filters[line], filters[order_no]...)
        $filters = $request->input('filters', []);

        // 3. 準備「線別」下拉選單的資料 (只撈該日期區間有的線別)
        $lines = DB::connection('oracle')
            ->table('NHT.nh_keikaku_no')
            ->where('country_cd', 'TNHT')
            ->whereBetween('tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd])
            ->distinct() // 去除重複
            ->pluck('line'); // 只取 line 欄位轉成陣列

        // ProductionPlanController.php 內的子查詢部分
        $subQuery = DB::connection('oracle')
            ->table('NHT.NH_SETSUDAN_MENTORI as nh_sm')
            ->select([
                'nh_sm.keikaku_no as JOIN_KEIKAKU_NO', // 明確大寫別名
                'nh_sm.line_cd',
                'nh_sm.seihin',
                DB::raw('SUM(nh_sm.SETSUDAN_SU) as TOTAL_SETSUDAN') // 確保大寫
            ])
            ->where('nh_sm.COUNTRY_CD', 'TNHT')
            ->whereBetween('nh_sm.SAGYO_YMD', [$dbDateStart, $dbDateEnd]) 
            ->groupBy('nh_sm.keikaku_no', 'nh_sm.line_cd', 'nh_sm.seihin');

        // 4. 開始建構主查詢
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
                'sm_sum.TOTAL_SETSUDAN' // 從子查詢帶入的欄位
            ])
            // Join 關聯表
            ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
            ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
            
            
            //-- 6. 修改 leftJoinSub 關聯條件 --
            ->leftJoinSub($subQuery, 'sm_sum', function ($join) {
                $join->on(DB::raw('TRIM(keikaku.keikaku_no)'), '=', DB::raw('TRIM(sm_sum.JOIN_KEIKAKU_NO)'));
            });
            
            // 基本條件
            ->where('keikaku.country_cd', 'TNHT')
            ->whereBetween('keikaku.tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd]);

        // ★★★ 5. 動態加入篩選條件 (Server-side Filtering) ★★★

        // 篩選：線別 (完全比對)
        $query->when(!empty($filters['line']), function ($q) use ($filters) {
            return $q->where('keikaku.line', $filters['line']);
        });

        $query->when(!empty($filters['keikaku_no']), function ($q) use ($filters) {
            $term = strtoupper($filters['keikaku_no']); // PHP 端先轉大寫
            // 寫法說明：where(欄位, 運算子, 值)
            return $q->where(DB::raw('UPPER(keikaku.keikaku_no)'), 'like', "%{$term}%");
        });

        // 篩選：製品 (修正)
        $query->when(!empty($filters['seihin']), function ($q) use ($filters) {
            $term = strtoupper($filters['seihin']);
            return $q->where(DB::raw('UPPER(keikaku.seihin)'), 'like', "%{$term}%");
        });

        // 篩選：訂單號 (修正)
        $query->when(!empty($filters['order_no']), function ($q) use ($filters) {
            $term = strtoupper($filters['order_no']);
            return $q->where(DB::raw('UPPER(keikaku.order_no)'), 'like', "%{$term}%");
        });

        // 6. 排序與分頁
        // ->appends($request->all()) 是關鍵！讓換頁時，篩選條件不會消失
        $plans = $query
            ->orderBy('keikaku.tonyu_yotei_ymd')
            ->orderBy('keikaku.line')
            ->orderBy('keikaku.keikaku_no')
            ->paginate(15)
            ->appends($request->all());
        // 7. 回傳 View
        return view('production_plan', [
            'plans'     => $plans,
            'lines'     => $lines,
            'dateStart' => $dateStartInput, // 回傳給前端顯示用 (Y-m-d)
            'dateEnd'   => $dateEndInput,
            'filters'   => $filters,    // 把使用者輸入的篩選字再傳回去，填入 input value
        ]);
    }
}