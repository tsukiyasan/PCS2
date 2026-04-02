<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        $dateStartInput = $request->input('date_start', date('Y-m-d'));
        $dateEndInput   = $request->input('date_end', $dateStartInput);

        $dbDateStart = str_replace('-', '', $dateStartInput);
        $dbDateEnd   = str_replace('-', '', $dateEndInput);

        $filters = $request->input('filters', []);

        // 3. 準備「線別」選單
        $lines = DB::connection('oracle')
            ->table('NHT.nh_keikaku_no')
            ->where('country_cd', 'TNHT')
            ->whereBetween('tonyu_yotei_ymd', [$dbDateStart, $dbDateEnd])
            ->distinct()
            ->pluck('line');

        // --- 修正 1：子查詢別名與分組明確化 ---
        $subQuery = DB::connection('oracle')
            ->table('NHT.NH_SETSUDAN_MENTORI')
            ->select([
                DB::raw('TRIM(KEIKAKU_NO) as JOIN_KEY'),
                DB::raw('SUM(SETSUDAN_SU) as TOTAL_SETSUDAN')
            ])
            ->where('COUNTRY_CD', 'TNHT')
            // 如果 SQL 查有數字但這裡沒有，通常是日期卡死，建議先放寬測試
            ->whereBetween('SAGYO_YMD', [$dbDateStart, $dbDateEnd]) 
            ->groupBy(DB::raw('TRIM(KEIKAKU_NO)'));

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
                // --- 修正 2：明確指定 sm_sum 的來源，並加上大寫別名防止被 PDO 丟棄 ---
                DB::raw('sm_sum.TOTAL_SETSUDAN as TOTAL_SETSUDAN') 
            ])
            ->leftJoin('NHT.nh_kikakusho_mst as kikaku', 'keikaku.seihin', '=', 'kikaku.seihin')
            ->leftJoin('NHT.nh_konpokeitai_mst as keitai', 'keikaku.keitai_cd', '=', 'keitai.keitai_cd')
            
            // --- 修正 3：關聯條件改回 TRIM 確保精確比對 ---
            ->leftJoinSub($subQuery, 'sm_sum', function ($join) {
                $join->on(DB::raw('TRIM(keikaku.keikaku_no)'), '=', 'sm_sum.JOIN_KEY');
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

        // 6. 分頁
        $plans = $query
            ->orderBy('keikaku.tonyu_yotei_ymd')
            ->orderBy('keikaku.line')
            ->orderBy('keikaku.keikaku_no')
            ->paginate(15)
            ->appends($request->all());

        return view('production_plan', [
            'plans'     => $plans,
            'lines'     => $lines,
            'dateStart' => $dateStartInput,
            'dateEnd'   => $dateEndInput,
            'filters'   => $filters,
        ]);
    }
}