<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
public function index(Request $request)
{
    // 1. 處理日期與參數
    $startDate = str_replace('-', '', $request->input('date', date('Ymd')));
    $endDate   = str_replace('-', '', $request->input('date2', $startDate));
    // 接收篩選參數
    $filterLine = $request->input('line');
    $filterOrder = $request->input('order_no');

    // 2. 準備下拉選單的資料 (例如：撈出當天有排程的所有線別，供使用者選擇)
    // 實務上通常會撈 Master 表，這裡示範從計劃表撈 Distinct
    $lines = DB::connection('oracle')
        ->table('NHT.nh_keikaku_no')
        ->where('country_cd', 'TNHT')
        ->whereBetween('tonyu_yotei_ymd', [$startDate, $endDate])
        ->distinct()
        ->pluck('line'); // 只要 line 欄位 array

    // 3. 主查詢
    $query = DB::connection('oracle')
        ->table('NHT.nh_keikaku_no as keikaku')
        ->select([
            'keikaku.keikaku_no', 'keikaku.line', 'keikaku.tonyu_yotei_ymd',
            'keikaku.seihin', 'keikaku.keikaku_su as tonyu_su',
            'keikaku.moku_ryohin_su as ryohin_su', 'keikaku.choku',
            'keikaku.order_no'
            // ... (其他欄位省略以節省篇幅) ...
        ])
        ->where('keikaku.country_cd', 'TNHT')
        ->whereBetween('keikaku.tonyu_yotei_ymd', [$startDate, $endDate]);

    // ★★★ 核心修改：加入動態篩選條件 ★★★
    
    // 如果有選線別，就加上這個 WHERE
    $query->when($filterLine, function ($q, $line) {
        return $q->where('keikaku.line', $line);
    });

    // 如果有輸入訂單號 (支援模糊搜尋)，就加上這個 WHERE
    $query->when($filterOrder, function ($q, $orderNo) {
        return $q->where('keikaku.order_no', 'like', "%{$orderNo}%");
    });

    // 4. 排序與分頁
    $plans = $query
        ->orderBy('keikaku.keikaku_no')
        ->paginate(20);

    // 5. 回傳 View (記得把搜尋條件帶回去，讓前端保持選取狀態)
    return view('production_plan', [
        'plans' => $plans,
        'lines' => $lines, // 傳遞線別清單
        'targetDate' => $request->input('date'),
        'targetDate2' => $request->input('date2'),
        'filterLine' => $filterLine,
        'filterOrder' => $filterOrder
    ]);
}
}