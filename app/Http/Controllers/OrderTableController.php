<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\orderTable;
use App\Models\Table;

class OrderTableController extends Controller
{
    public function index()
    {
        //
    }

    public function getTableInfo($token)
    {
        $table = Table::where('qr_token', $token)->first();

        if (!$table) {
            return response()->json(['message' => 'Mã bàn không toàn tại'], 403);
        }

        $orderTable = orderTable::where('table_id', $table->id)
            ->where('status', 'serve')
            ->first();
        if (!$orderTable) {
            return response()->json(['message' => 'Bàn chưa có hoá đơn'], 404);
        }
        return response()->json([
            'số bàn' => $table->id,
            'hoá đơn' => $orderTable,
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }
}