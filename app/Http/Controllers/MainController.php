<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\pcsdb;

class MainController extends Controller
{
    public function index()
    {
        $cards = pcsdb::from('HR_CARD_SOURCE') // 明確指定表名
                    ->select('EMP_NO', 'PUNCH_TIME')  // 假設有這兩個欄位
                    ->take(10)                        // 只抓 10 筆以免跑太久
                    ->get();

        // 將資料傳送到 View (頁面)
        // 'card_list' 是我們等一下要建立的 blade 檔案名稱
        return view('card_list', ['cards' => $cards]);
    }
}