<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class StockController extends Controller
{
    public function index(Request $request)
    {
        // 1. 取得篩選條件與日期（保留結構，供畫面元件使用）
        $dateStartInput = $request->get('date_start', '2026-06-01');
        $dateEndInput   = $request->get('date_end', '2026-06-01');
        $filters        = $request->get('filters', []);

        // 2. 建立符合圖片範例的假資料陣列
        $dummyData = [
            (object)[
                'nengetsu'      => '202604',
                'yoto_name'     => 'TFT',
                'seihin'        => '2SHA6888BQCM',
                'customer_name' => '客戶 A', // 圖片中此處為空，補上假名稱方便辨識
                'konbao_ymd'    => '260330',  // 依圖片填入捆包日範例
                'maisu'         => 20,
            ],
            (object)[
                'nengetsu'      => '202604',
                'yoto_name'     => 'TFT',
                'seihin'        => '2SHA6888BQCM',
                'customer_name' => '客戶 B',
                'konbao_ymd'    => '260330',
                'maisu'         => 20,
            ],
            (object)[
                'nengetsu'      => '202604',
                'yoto_name'     => 'TFT',
                'seihin'        => '2SHA6888BQCM',
                'customer_name' => '',
                'konbao_ymd'    => '260330',
                'maisu'         => 20,
            ],
            (object)[
                'nengetsu'      => '202604',
                'yoto_name'     => 'TFT',
                'seihin'        => '2SHA6888BQCM',
                'customer_name' => '客戶 C',
                'konbao_ymd'    => '260330',
                'maisu'         => 20,
            ],
            (object)[
                'nengetsu'      => '202604',
                'yoto_name'     => 'TFT',
                'seihin'        => '2SHA6888BQCM',
                'customer_name' => '',
                'konbao_ymd'    => '260330',
                'maisu'         => 20,
            ],
        ];

        // 將陣列轉為 Collection 物件，方便畫面的 $stocks->sum('maisu') 能正常執行
        $collection = collect($dummyData);

        // 3. 手動做一個假的 Paginator 分頁物件，讓頁面底下的 $stocks->links() 正常運作不報錯
        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->all();
        
        $stocks = new LengthAwarePaginator(
            $currentItems, 
            $collection->count(), 
            $perPage, 
            $currentPage, 
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        // 4. 用途下拉選單的假選項
        $yotoOptions = ['TFT', 'OLED', 'STN'];

        // 5. 回傳至 stock.blade.php
        return view('stock', [
            'stocks'      => $stocks,
            'yotoOptions' => $yotoOptions,
            'dateStart'   => $dateStartInput,
            'dateEnd'     => $dateEndInput,
            'filters'     => $filters,
        ]);
    }
}