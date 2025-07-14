<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Table;
use App\Models\Customer;
use App\Models\Voucher;
use App\Models\OrderTable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->whereHas('customer', function ($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%");
            })->orWhereHas('tables', function ($q) use ($keyword) {
                $q->where('table_number', 'like', "%$keyword%");
            })->orWhere('status', 'like', "%$keyword%");
        }

        $orders = $query->with('customer', 'tables')->latest()->get();

        return response()->json($orders);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }

    public function show($id)
    {
        $order = Order::with(['customer', 'tables', 'items.food', 'items.combo'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    public function statsDashbroad()
    {
        $totalOrder = Order::where('status', 'confirmed')->count();
        $totalRevenue = Order::where('status', 'confirmed')->sum('total_price');
        $totalCustomers = Customer::count();

        return response()->json([
            "statOrder" => $totalOrder,
            "statTotal" => $totalRevenue,
            "statCustomer" => $totalCustomers
        ]);
    }

    public function bookTables(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'guest_count' => 'required|integer|min:1',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required|date_format:H:i:s',
            'payment_method' => 'required|in:cash,momo,vnpay',
            'payment_code' => 'nullable|string',
            'voucher_id' => 'nullable|exists:vouchers,id',
            'note' => 'nullable|string',
            'total_price' => 'required|numeric',
            'foods' => 'sometimes|array',
            'foods.*.food_id' => 'required_with:foods|exists:foods,id',
            'foods.*.quantity' => 'required_with:foods|integer|min:1',
            'foods.*.price' => 'required_with:foods|numeric',
            'combos' => 'sometimes|array',
            'combos.*.combo_id' => 'required_with:combos|exists:combos,id',
            'combos.*.quantity' => 'required_with:combos|integer|min:1',
            'combos.*.price' => 'required_with:combos|numeric',
        ]);

        $date = $request->reservation_date;
        $time = $request->reservation_time;
        $guestCount = $request->guest_count;
        $voucher_id = $request->voucher_id;
        $availableTables = Table::whereDoesntHave('orderTables', function ($q) use ($date, $time) {
            $q->where('reservation_date', $date)
                ->where('reservation_time', $time);
        })->orderByDesc('max_guests')->get();

        $selectedTables = [];
        $remainingGuests = $guestCount;

        foreach ($availableTables as $table) {
            if ($remainingGuests <= 0) break;
            $selectedTables[] = $table->id;
            $remainingGuests -= $table->max_guests;
        }

        if ($remainingGuests > 0) {
            $largestTable = $availableTables->first();
            if (!$largestTable) {
                return response()->json(['message' => 'Không còn bàn nào trống trong khung giờ này!'], 422);
            }
            $selectedTables = [$largestTable->id];
        }

        $order = Order::create([
            'customer_id' => $request->customer_id,
            'payment_method' => $request->payment_method,
            'voucher_id' => $request->voucher_id,
            'total_price' => $request->total_price,
            'status' => 'pending',
            'note' => $request->note,
            'payment_code' => strtoupper(uniqid('PAY')),
        ]);

        $orderTableIds = [];
        foreach ($selectedTables as $tableId) {
            $orderTableIds[] = DB::table('order_tables')->insertGetId([
                'order_id' => $order->id,
                'table_id' => $tableId,
                'reservation_date' => $date,
                'reservation_time' => $time,
                'status' => 'serve',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($request->foods ?? [] as $food) {
            DB::table('order_items')->insert([
                'order_id' => $order->id,
                'food_id' => $food['food_id'],
                'quantity' => $food['quantity'],
                'price' => $food['price'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($request->combos ?? [] as $combo) {
            DB::table('order_items')->insert([
                'order_id' => $order->id,
                'combo_id' => $combo['combo_id'],
                'quantity' => $combo['quantity'],
                'price' => $combo['price'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => $remainingGuests > 0 ? 'Không đủ bàn, đã dồn tất cả khách vào 1 bàn lớn nhất còn trống!' : 'Đặt bàn thành công',
            'order_id' => $order->id,
            'ids_tables' => $orderTableIds,
            'selected_tables' => $selectedTables,
            'ordered_foods' => $request->foods ?? [],
            'payment' => [
                'order_id' => $order->id,
                'amount' => $order->total_price,
                'payment_method' => $order->payment_method,
                'payment_code' => $order->payment_code,
                'status' => $order->status,
            ],
        ]);
    }

    public function addPoint($order)
    {
        $customer = Customer::find($order->customer_id);
        if (!$customer) {
            return response()->json(['message' => 'Khách hàng không tồn tại'], 404);
        }

        if ($order->total_price >= 200000) {
            $pointsEarned = floor($order->total_price / 200000) * 10;
            $customer->point += $pointsEarned;
            $customer->point_available += $pointsEarned;
            $customer->membership_level = $this->calculateMembershipLevel($customer->point);
            $customer->save();

            return response()->json([
                'message' => 'Điểm thưởng đã được cộng',
                'points' => $customer->point,
            ]);
        }

        return response()->json([
            'message' => 'Hóa đơn chưa đủ điều kiện để cộng điểm',
            'points' => $customer->point,
        ]);
    }

    private function calculateMembershipLevel($points)
    {
        if ($points >= 5000) return 'Kim Cương';
        if ($points >= 1000) return 'Vàng';
        if ($points >= 100) return 'Bạc';
        return 'thành viên';
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'manager') {
            return response()->json(['message' => 'Bạn không có quyền cập nhật trạng thái món ăn'], 403);
        }
        $order = Order::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,success,cancelled'
        ]);

        $order->status = $validated['status'];
        $order->save();

        if ($order->status == 'success') {
            $this->addPoint($order);
            return response()->json(['message' => 'Đã tích điểm', 'order' => $order]);
        }

        return response()->json(['message' => 'Trạng thái đơn hàng đã được cập nhật', 'order' => $order]);
    }

    public function orderHistory($id_customer)
    {
        $orders = Order::where('customer_id', $id_customer)
            ->orderBy('created_at', 'desc')
            ->with(['orderItems.food', 'orderItems.combo'])
            ->get();

        $result = $orders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'total_price' => $order->total_price,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'food_name' => optional($item->food)->name,
                        'combo_name' => optional($item->combo)->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                }),
            ];
        });

        return response()->json($result);
    }

    public function cancelOrder($order_id)
    {
        $order = Order::find($order_id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $order->status = 'cancelled';
        $order->save();
        return response()->json(['message' => 'Order cancelled successfully']);
    }
}