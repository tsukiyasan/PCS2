<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon; 

class StockController extends Controller
{
    public function index(Request $request)
    {
        // 1. 取得篩選條件與日期（從畫面元件傳入的 YYYY-MM-DD 格式）
        $dateStartInput = $request->get('date_start', '2026-06-01');
        $dateEndInput   = $request->get('date_end', '2026-06-30'); // 預設改成月底範圍
        $filters        = $request->get('filters', []);

        // 核心修正 1：將 YYYY-MM-DD 格式轉換為資料庫比對用的 YYYYMMDD 格式 (例如：20260601)
        $start = Carbon::parse($dateStartInput)->format('Ymd');
        $end   = Carbon::parse($dateEndInput)->format('Ymd');

        // 報表顯示的「年月」欄位，依據查詢起點的月份自動產生 (例如：202606)
        $nengetsu = Carbon::parse($dateStartInput)->format('Ym');

        // 2. 準備 Raw SQL 語法
        $sql = "
            SELECT
                :nengetsu AS nengetsu,
                yoto.yoto_name AS yoto_name,
                summary.seihin AS seihin,
                kikaku.shoshu_cd AS customer_name,
                summary.min_konpo_ymd AS konbao_ymd,
                summary.total_maisu AS maisu,
                summary.latest_ukeharai_ymd AS ukeharai_ymd
            FROM (
                SELECT 
                    TRIM(s.seihin) AS seihin, 
                    MAX(s.ukeharai_ymd) AS latest_ukeharai_ymd,
                    MIN(SUBSTR(s.konpo_konpo_ymd, 1, 8)) AS min_konpo_ymd,
                    SUM(s.gesho_zan + s.kotei_ukeire_su + s.henpin_ukeire_su + s.furi_ukeire_su + s.hason_ukeire_su + s.gaichu_ukeire_su 
                        - s.ryohin_harai_su - s.hason_harai_su - s.furi_harai_su - s.hoju_harai_su 
                        + s.hosei_su - s.daino_su - s.zaiso_su - s.sample_su) AS total_maisu
                FROM nh_seihin_ukeharai s
                WHERE s.kotei_cd IN ('11','15','17','19','20','22','S01','S02','S03')
                  AND TRIM(s.country_cd) = 'TNHT' 
                  
                  -- 核心修正 2：改為大於等於開始日、小於等於結束日
                  AND s.ukeharai_ymd >= :date_start
                  AND s.ukeharai_ymd <= :date_end
                  
                GROUP BY TRIM(s.seihin)
            ) summary
            LEFT JOIN nh_kikakusho_mst kikaku ON summary.seihin = TRIM(kikaku.seihin)
            LEFT JOIN nh_yoto_mst yoto        ON TRIM(kikaku.yoto) = TRIM(yoto.yoto_cd)
            WHERE summary.total_maisu <> 0
            ORDER BY summary.seihin ASC
        ";

        // 核心修正 3：傳入綁定參數
        $dbData = DB::select($sql, [
            'nengetsu'   => $nengetsu,
            'date_start' => $start, // 綁定開始日 (例如: 20260601)
            'date_end'   => $end,   // 綁定結束日 (例如: 20260630)
        ]);

        // 3. 將資料庫查出的陣列轉為 Collection 物件
        $collection = collect($dbData);

        // 4. 手動做一個 Paginator 分頁物件
        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $stocks = new LengthAwarePaginator(
            $currentItems, 
            $collection->count(), 
            $perPage, 
            $currentPage, 
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        // 5. 用途下拉選單的假選項
        $yotoOptions = ['TFT', 'OLED', 'STN'];

        // 6. 回傳至 stock.blade.php
        return view('stock', [
            'stocks'      => $stocks,
            'yotoOptions' => $yotoOptions,
            'dateStart'   => $dateStartInput,
            'dateEnd'     => $dateEndInput,
            'filters'     => $filters,
        ]);
    }
}