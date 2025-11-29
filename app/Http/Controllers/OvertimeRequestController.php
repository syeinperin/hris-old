<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        OvertimeRequest::create($request->all() + ['user_id' =>  Auth::id()]);

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(OvertimeRequest $overtimeRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OvertimeRequest $overtimeRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OvertimeRequest $overtimeRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OvertimeRequest $overtimeRequest)
    {
        //
    }

    public function changeStatus(Request $request, int $overtimerequestId)
    {
        $overtimeRequest = OvertimeRequest::find($overtimerequestId);
        $overtimeRequest->update(['status' => $request->status]);

        return redirect()->back();
    }
}
