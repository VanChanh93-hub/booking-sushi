<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(OrderItem::all());
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




    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:tables,id',
            'food_id' => 'nullable|integer|exists:foods,id',
            // 'combo_id' => 'nullable|integer|exists:combos,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);
        $order = Order::where('id', $validated['order_id'])
            ->whereIn('status', ['pending', 'serve'])
            ->latest()
            ->first();;
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng đang phục vụ cho bàn này.'], 404);
        }
        $validated['order_id'] = $order->id;

        $orderItem = OrderItem::create($validated);
        return response()->json([
            'message' => 'Thêm món thành công',
            'order_item' => $orderItem,
        ], 201);
    }

    // Cập nhật trạng thái của order item
    public function updateStatus(Request $request, $id)
    {
        $orderItem = OrderItem::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,served,done,cancelled'
        ]);

        $orderItem->status = $validated['status'];
        $orderItem->save();

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công',
            'order_item' => $orderItem,
        ]);
    }
}
