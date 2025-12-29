<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // ⚠️ 記得引入 DB

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        // 1. 設定查詢日期 (預設今天，或是網址帶入 ?date=20251226)
        $targetDate = $request->input('date', '20251226'); 

        // 2. 準備 SQL
        // 使用 HEREDOC 語法 (<<<SQL ... SQL;) 可以直接貼上多行 SQL，不用一直串接字串
        $sql = <<<SQL
SELECT
    keikaku_no,
    line,
    tonyu_yotei_ymd,
    seihin,
    sunpo_s,
    sunpo_l,
    itaatsu,
    tonyu_su,
    ryohin_su,
    choku,
    keitai_cd,
    keitai_name,
    kotei_name1,
    kotei_name2,
    kotei_name3,
    delete_kbn,
    order_no,
    upd_date,
    '' as status
FROM
    (
        SELECT
            keikaku.keikaku_no keikaku_no,
            keikaku.line line,
            keikaku.tonyu_yotei_ymd tonyu_yotei_ymd,
            keikaku.seihin seihin,
            kikaku.sunpo_s sunpo_s,
            kikaku.sunpo_l sunpo_l,
            kikaku.itaatsu itaatsu,
            keikaku.keikaku_su tonyu_su,
            keikaku.moku_ryohin_su ryohin_su,
            keikaku.choku choku,
            keikaku.keitai_cd keitai_cd,
            keitai.keitai_ryaku_lang4 as keitai_name,
            kotei1.kotei_name_lang4 as kotei_name1,
            kotei2.kotei_name_lang4 as kotei_name2,
            kotei3.kotei_name_lang4 as kotei_name3,
            keikaku.delete_kbn delete_kbn,
            keikaku.order_no order_no,
            keikaku.upd_date upd_date
        FROM
            nh_keikaku_no keikaku,
            nh_kikakusho_mst kikaku,
            nh_konpokeitai_mst keitai,
            nh_kotei_mst kotei1,
            nh_kotei_mst kotei2,
            nh_kotei_mst kotei3
        WHERE
            keikaku.country_cd = 'TNHT'
            AND keikaku.seihin = kikaku.seihin(+)
            AND keikaku.keitai_cd = keitai.keitai_cd(+)
            AND keikaku.kotei1 = kotei1.kotei_cd(+)
            AND keikaku.kotei2 = kotei2.kotei_cd(+)
            AND keikaku.kotei3 = kotei3.kotei_cd(+)
            -- ⚠️ 這裡改成參數綁定，不要寫死日期
            AND keikaku.tonyu_yotei_ymd = :ymd
    )
ORDER BY
    keikaku_no ASC,
    line ASC,
    seihin ASC,
    choku ASC,
    delete_kbn ASC
SQL;

        // 3. 執行查詢
        // 使用 select 方法，第二個參數是用來替換上面 SQL 中的 :ymd
        $plans = DB::connection('oracle')->select($sql, ['ymd' => $targetDate]);

        // 4. 回傳 View
        return view('production_plan', ['plans' => $plans, 'targetDate' => $targetDate]);
    }
}