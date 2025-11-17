<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataAnalyticsController extends Controller
{
    
    public function index(Request $request)
    {
        // Example queries — adjust to your actual schema/views
        
        $headcount   = DB::table('v_kpi_headcount')->get();
        $turnover    = DB::table('v_kpi_turnover')->get();
        $timeToHire  = DB::table('v_kpi_time_to_hire')->get();   // ✅ Added
        $absenteeism = DB::table('v_kpi_absenteeism')->get();
        $otCost      = DB::table('v_kpi_ot_cost')->get();
        $genderMix   = DB::table('v_kpi_gender_mix')->get();

        return view('reports.analytics', compact(
            'headcount',
            'turnover',
            'timeToHire',   // ✅ Send to view
            'absenteeism',
            'otCost',
            'genderMix'
        ));
    }
}
