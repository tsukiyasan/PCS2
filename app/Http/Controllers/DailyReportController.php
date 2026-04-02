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

        // 3. 建立子查詢 (完全還原你成功的 SQL，並拿掉日期限制以防實績落後)
        $subQuery = DB::connection('oracle')
            ->table('NHT.NH_SETSUDAN_MENTORI as nh_sm')
            ->select([
                'nh_sm.keikaku_no',
                DB::raw('SUM(nh_sm.setsudan_su) as total_setsudan')
            ])
            ->where('nh_sm.country_cd', 'TNHT')
            // 拿掉日期限制，只要編號一樣，所有實績通通算進來
            ->groupBy('nh_sm.keikaku_no');

        // 4. 主查詢
        $query = DB::connection('oracle')
            ->table('NHT.nh_keikaku_no as keikaku')
            ->select([
                'keikaku.*',
                'kikaku.itaatsu',
                // ★★★ 關鍵：強制 NVL 補 0，並指定與 JSON 一致的小寫別名 ★★★
                DB::raw('NVL(sm_sum.total_setsudan, 0) as total_setsudan') 
            ])
            ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
            ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
            
            // 還原你手動 SQL 的精確 Join 條件 (不使用 TRIM)
            ->leftJoinSub($subQuery, 'sm_sum', function ($join) {
                $join->on('keikaku.keikaku_no', '=', 'sm_sum.keikaku_no');
            })
            
            ->where('keikaku.country_cd', 'TNHT')
            ->whereBetween('keikaku.tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd]);

        // 5. 動態篩選
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

        $query->when(!empty($filters['order_no']), function ($q) use ($filters) {
            $term = strtoupper(trim($filters['order_no']));
            return $q->where(DB::raw('UPPER(keikaku.order_no)'), 'like', "%{$term}%");
        });

        // 6. 排序與分頁
        $plans = $query
            ->orderBy('keikaku.tonyu_yotei_ymd', 'ASC')
            ->orderBy('keikaku.line', 'ASC')
            ->orderBy('keikaku.keikaku_no', 'ASC')
            ->paginate(15)
            ->appends($request->all());

        return view('production_daily_report', [
            'plans'     => $plans,
            'lines'     => $lines,
            'dateStart' => $dateStartInput,
            'dateEnd'   => $dateEndInput,
            'filters'   => $filters,
        ]);
    }
}