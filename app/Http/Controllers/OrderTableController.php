<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\orderTable;
use App\Models\Table;

class OrderTableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getTableInfo($token)
    {
        $table = Table::where('qr_token', $token)->first();

        if (!$table) {
            return response()->json(['message' => 'Mã bàn không tồn tại'], 403);
        }

        // Tìm orderTable đang phục vụ
        $orderTable = orderTable::where('table_id', $table->id)
            ->where('status', 'serve')
            ->with('order') // nếu cần chi tiết đơn hàng
            ->first();

        if ($orderTable) {
            return response()->json([
                'message' => 'Bàn đang được phục vụ',
                'table_id' => $table->id,
                'order_id' => $orderTable->order->id ?? null,
                'status' => $orderTable->status,
            ]);
        }

        // Kiểm tra xem bàn này đã từng có đặt bàn chưa
        $existingOrderTable = orderTable::where('table_id', $table->id)->first();

        if (!$existingOrderTable) {
            // Tạo đơn hàng mới
            $order = Order::create([
                'total' => 0,
                'status' => 'pending', // hoặc trạng thái mặc định bạn dùng
            ]);

            // Gắn đơn hàng vào bàn
            $orderTable = orderTable::create([
                'table_id' => $table->id,
                'order_id' => $order->id,
                'status' => 'serve',
            ]);

            return response()->json([
                'message' => 'Đã tạo đơn hàng mới và phục vụ bàn',
                'table_id' => $table->id,
                'order_id' => $order->id,
                'status' => $orderTable->status,
            ]);
        }

        // Nếu đã từng có đặt bàn nhưng không ở trạng thái 'serve'
        return response()->json([
            'message' => 'Bàn đã được đặt trước nhưng chưa phục vụ',
            'table_id' => $table->id,
            'order_id' => $existingOrderTable->order_id,
            'status' => $existingOrderTable->status,
        ], 403);
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