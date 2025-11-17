<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function summary()
    {
        $ttl = 600;

        $hcSeries = Cache::remember('dash_hc_series', $ttl, function () {
            return DB::table('v_kpi_headcount')
                ->selectRaw('month, SUM(headcount) AS total')
                ->groupBy('month')->orderBy('month')->get();
        });

        $turnSeries = Cache::remember('dash_turn_series', $ttl, function () {
            return DB::table('v_kpi_turnover')
                ->select('month','separations')->orderBy('month')->get();
        });

        return response()->json([
            'cards' => [
                'headcount_now'    => (int) ($hcSeries->last()->total ?? 0),
                'absenteeism_pct'  => 0.00,  // placeholder with your current schema
                'avg_time_to_hire' => 0.0,   // placeholder
                'ot_cost'          => 0.00,  // placeholder
            ],
            'series' => [
                'headcount' => $hcSeries,
                'turnover'  => $turnSeries,
            ],
        ]);
    }
}
