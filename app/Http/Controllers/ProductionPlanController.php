<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // 引入 DB

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        // 1. 設定查詢日期 (預設今天，或是網址帶入 ?date=20251226)
        $targetDate = $request->input('date', date('Ymd')); 

        // 2. 準備 SQL
        // 使用 HEREDOC 語法 (<<<SQL ... SQL;) 可以直接貼上多行 SQL，不用一直串接字串
        $sql = <<<SQL
SELECT
    keikaku.keikaku_no,
    keikaku.line,
    keikaku.tonyu_yotei_ymd,
    keikaku.seihin,
    kikaku.sunpo_s,
    kikaku.sunpo_l,
    kikaku.itaatsu,
    keikaku.keikaku_su AS tonyu_su,
    keikaku.moku_ryohin_su AS ryohin_su,
    keikaku.choku,
    keikaku.keitai_cd,
    keitai.keitai_ryaku_lang4 AS keitai_name,
    kotei1.kotei_name_lang4 AS kotei_name1,
    kotei2.kotei_name_lang4 AS kotei_name2,
    kotei3.kotei_name_lang4 AS kotei_name3,
    keikaku.delete_kbn,
    keikaku.order_no,
    keikaku.upd_date,
    '' AS status
FROM
    -- 1. 主表 (生產計劃)
    NHT.nh_keikaku_no keikaku

    -- 2. 關聯產品規格 (取代 kikaku.seihin(+))
    LEFT JOIN NHT.nh_kikakusho_mst kikaku 
        ON keikaku.seihin = kikaku.seihin

    -- 3. 關聯包裝形態 (取代 keitai.keitai_cd(+))
    LEFT JOIN NHT.nh_konpokeitai_mst keitai 
        ON keikaku.keitai_cd = keitai.keitai_cd

    -- 4. 關聯工程 1 (取代 kotei1.kotei_cd(+))
    LEFT JOIN NHT.nh_kotei_mst kotei1 
        ON keikaku.kotei1 = kotei1.kotei_cd

    -- 5. 關聯工程 2 (取代 kotei2.kotei_cd(+))
    LEFT JOIN NHT.nh_kotei_mst kotei2 
        ON keikaku.kotei2 = kotei2.kotei_cd

    -- 6. 關聯工程 3 (取代 kotei3.kotei_cd(+))
    LEFT JOIN NHT.nh_kotei_mst kotei3 
        ON keikaku.kotei3 = kotei3.kotei_cd

WHERE
    keikaku.country_cd = 'TNHT'
    AND keikaku.tonyu_yotei_ymd = :ymd

ORDER BY
    keikaku.keikaku_no ASC,
    keikaku.line ASC,
    keikaku.seihin ASC,
    keikaku.choku ASC,
    keikaku.delete_kbn ASC
SQL;

        // 3. 執行查詢
        // 使用 select 方法，第二個參數是用來替換上面 SQL 中的 :ymd
        $plans = DB::connection('oracle')->select($sql, ['ymd' => $targetDate]);

        // 4. 回傳 View
        return view('production_plan', ['plans' => $plans, 'targetDate' => $targetDate]);
    }
}