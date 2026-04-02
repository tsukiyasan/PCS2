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

        // 3. 主查詢：【純粹查詢計畫與主檔，絕對不要 Join 實績表】
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
            ])
            ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
            ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
            ->where('keikaku.country_cd', 'TNHT')
            ->whereBetween('keikaku.tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd]);

        // 動態篩選
        $query->when(!empty($filters['line']), function ($q) use ($filters) {
            return $q->where('keikaku.line', $filters['line']);
        });
        $query->when(!empty($filters['keikaku_no']), function ($q) use ($filters) {
            $term = strtoupper(trim($filters['keikaku_no']));
            return $q->where(DB::raw('UPPER(keikaku.keikaku_no)'), 'like', "%{$term}%");
        });
        $query->when(!empty($filters['seihin']), function ($q) use ($filters) {
            $term = strtoupper(trim($filters['seihin']));
            return $q->where(DB::raw('UPPER(keikaku.seihin)'), 'like', "%{$term}%");
        });

        // 4. 執行分頁
        $plans = $query
            ->orderBy('keikaku.tonyu_yotei_ymd', 'ASC')
            ->orderBy('keikaku.line', 'ASC')
            ->orderBy('keikaku.keikaku_no', 'ASC')
            ->paginate(15)
            ->appends($request->all());

        // ★★★ 5. 終極 PHP 端資料合併 (防禦所有型態問題) ★★★
        $keikakuNos = [];
        $items = $plans->items();
        
        // 抓出當頁所有的計畫編號 (兼容陣列與物件)
        foreach ($items as $item) {
            $k = is_object($item) ? $item->keikaku_no : $item['keikaku_no'];
            if (!empty($k)) {
                $keikakuNos[] = trim($k);
            }
        }
        $keikakuNos = array_unique($keikakuNos);

        // 準備實績字典
        $actualSums = [];
        if (!empty($keikakuNos)) {
            // 用 IN 語法一次把實績撈回來
            $sums = DB::connection('oracle')
                ->table('NHT.NH_SETSUDAN_MENTORI')
                ->select([
                    DB::raw('TRIM(KEIKAKU_NO) as keikaku_no'),
                    DB::raw('SUM(SETSUDAN_SU) as total_setsudan')
                ])
                ->where('COUNTRY_CD', 'TNHT')
                ->whereIn(DB::raw('TRIM(KEIKAKU_NO)'), $keikakuNos)
                ->groupBy(DB::raw('TRIM(KEIKAKU_NO)'))
                ->get();

            // 寫入字典
            foreach ($sums as $sum) {
                $k = is_object($sum) ? trim($sum->keikaku_no) : trim($sum['keikaku_no']);
                $v = is_object($sum) ? $sum->total_setsudan : $sum['total_setsudan'];
                $actualSums[$k] = $v;
            }
        }

        // 把實績強硬塞回分頁資料中
        foreach ($items as &$plan) {
            $k = is_object($plan) ? trim($plan->keikaku_no) : trim($plan['keikaku_no']);
            $val = isset($actualSums[$k]) ? $actualSums[$k] : 0;

            // 無論底層是 Object 還是 Array，通通塞進去！
            if (is_object($plan)) {
                $plan->total_setsudan = $val;
            } else {
                $plan['total_setsudan'] = $val;
            }
        }

        // 為了確保變更生效，我們如果連這樣 JSON 都沒有，就直接中斷印出！
        // dd($items); // <--- 如果等等畫面還是沒資料，請把這行最前面的雙斜線拿掉，看網頁印出什麼！

        // 6. 回傳視圖
        return view('production_plan', [
            'plans'     => $plans,
            'lines'     => $lines,
            'dateStart' => $dateStartInput,
            'dateEnd'   => $dateEndInput,
            'filters'   => $filters,
        ]);
    }
}