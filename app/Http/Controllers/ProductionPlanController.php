<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
public function index(Request $request)
{
    // 1. 日期區間處理 (必填或預設當天)
    $startDate = str_replace('-', '', $request->input('date_start', date('Ymd')));
    $endDate   = str_replace('-', '', $request->input('date_end', $startDate));

    // 2. 接收表格內的篩選陣列，例如 filters[line], filters[order_no]
    $filters = $request->input('filters', []);

    // 3. 準備下拉選單用的資料 (線別)
    $lines = DB::connection('oracle')
        ->table('NHT.nh_keikaku_no')
        ->where('country_cd', 'TNHT')
        ->whereBetween('tonyu_yotei_ymd', [$startDate, $endDate])
        ->distinct()->pluck('line');

    // 4. 建構查詢
    $query = DB::connection('oracle')
        ->table('NHT.nh_keikaku_no as keikaku')
        // ... (select 欄位同前) ...
        ->select([
             'keikaku.keikaku_no', 'keikaku.line', 'keikaku.tonyu_yotei_ymd',
             'keikaku.seihin', 'keikaku.keikaku_su as tonyu_su',
             'keikaku.moku_ryohin_su as ryohin_su', 'keikaku.choku',
             'keikaku.order_no', 'kikaku.sunpo_s', 'kikaku.sunpo_l', 'kikaku.itaatsu'
             // ... 其他需要的關聯欄位
        ])
        ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
        ->where('keikaku.country_cd', 'TNHT')
        ->whereBetween('keikaku.tonyu_yotei_ymd', [$startDate, $endDate]);

    // ★★★ 針對表格欄位進行動態過濾 ★★★
    
    // 過濾：線別 (完全匹配)
    $query->when(isset($filters['line']) && $filters['line'] !== '', function ($q) use ($filters) {
        return $q->where('keikaku.line', $filters['line']);
    });

    // 過濾：訂單號 (模糊搜尋)
    $query->when(isset($filters['order_no']) && $filters['order_no'] !== '', function ($q) use ($filters) {
        return $q->where('keikaku.order_no', 'like', "%{$filters['order_no']}%");
    });

    // 過濾：製品 (模糊搜尋)
    $query->when(isset($filters['seihin']) && $filters['seihin'] !== '', function ($q) use ($filters) {
        return $q->where('keikaku.seihin', 'like', "%{$filters['seihin']}%");
    });

    // 5. 分頁與回傳
    // append($request->all()) 很重要！這讓分頁連結 (第2頁) 能記住你的篩選條件
    $plans = $query->orderBy('keikaku.keikaku_no')->paginate(20)->appends($request->all());

    return view('production_plan', [
        'plans' => $plans,
        'lines' => $lines,
        'dateStart' => $request->input('date_start', date('Y-m-d')), // 保持原本輸入格式回傳給前端
        'dateEnd'   => $request->input('date_end', date('Y-m-d')),
        'filters'   => $filters, // 把篩選條件傳回去顯示
    ]);
}
}