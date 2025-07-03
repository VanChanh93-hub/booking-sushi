<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Food;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:tables,id',
            'food_id' => 'nullable|integer|exists:foods,id',
            'combo_id' => 'nullable|integer|exists:combos,id',
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
        $food = Food::find($validated['food_id']);
        return response()->json([
            'message' => 'Thêm món thành công',
            'tên món ăn' => $food->name,
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


    public function getItemsByOrderId($orderId)
    {

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
        }

        $items = OrderItem::where('order_id', $orderId)
            ->with('food:id,name') // chỉ load id và name từ bảng food
            ->get()
            ->map(function ($item) {
                return [
                    'food_name' => $item->food->name ?? null,
                    'combo_id' => $item->combo_id ?? "không có",
                ];
            });

        if ($items->isEmpty()) {
            return response()->json(['message' => 'Không có món nào trong đơn hàng này'], 404);
        }

        return response()->json([
            'message' => 'Danh sách món ăn trong đơn hàng',
            'data' => $items
        ]);
    }

    public function removeItem(Request $request, $id)
    {
        $orderItem = OrderItem::find($id);
        if (!$orderItem) {
            return response()->json(['message' => 'Món ăn không tồn tại'], 404);
        }

        $order = Order::find($orderItem->order_id);
        if (!$order || !in_array($order->status, ['pending', 'serve'])) {
            return response()->json(['message' => 'Không thể xóa món ăn từ đơn hàng đã hoàn thành hoặc hủy'], 400);
        }

        $orderItem->delete();
        return response()->json(['message' => 'Đã xóa món ăn khỏi đơn hàng'], 200);
    }

    public function updateItem(Request $request, $id)
    {
        $orderItem = OrderItem::find($id);
        if (!$orderItem) {
            return response()->json(['message' => 'Món ăn không tồn tại'], 404);
        }

        $validated = $request->validate([
            'food_id' => 'nullable|integer|exists:foods,id',
            'combo_id' => 'nullable|integer|exists:combos,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $order = Order::find($orderItem->order_id);
        if (!$order || !in_array($order->status, ['pending', 'serve'])) {
            return response()->json(['message' => 'Không thể cập nhật món ăn từ đơn hàng đã hoàn thành hoặc hủy'], 400);
        }

        $orderItem->update($validated);
        return response()->json([
            'message' => 'Cập nhật món ăn thành công',
            'order_item' => $orderItem,
        ], 200);
    }
    public function bestSellers(Request $request)
    {
        $top = $request->input('top', 5);

        // Lấy danh sách tổng số lượng từng món
        $orderItems = OrderItem::selectRaw('food_id, SUM(quantity) as total_quantity')
            ->groupBy('food_id')
            ->orderByDesc('total_quantity')
            ->take($top)
            ->get();

        if ($orderItems->isEmpty()) {
            return response()->json(['message' => 'Không có món ăn nào được bán'], 404);
        }

        // Lấy tất cả food_id trong danh sách này
        $foodIds = $orderItems->pluck('food_id')->toArray();

        // Lấy thông tin các món ăn tương ứng
        $foods = Food::whereIn('id', $foodIds)->get()->keyBy('id');

        // Gán thông tin món ăn vào từng item
        $result = $orderItems->map(function ($item) use ($foods) {
            return [
                'food_id' => $item->food_id,
                'total_quantity' => $item->total_quantity,
                'food' => $foods->get($item->food_id),
            ];
        });

        return response()->json([
            'message' => 'Danh sách món ăn bán chạy',
            'data' => $result
        ]);
    }
}