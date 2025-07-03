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

class OrderController extends Controller
{
    //  Danh sách đơn
    public function index(Request $request)
    {
        $query = Order::with('table', 'customer')->latest();

        if ($request->has('keyword')) {
            $keyword = $request->keyword;

            $query->whereHas('customer', function ($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%");
            })->orWhereHas('table', function ($q) use ($keyword) {
                $q->where('table_number', 'like', "%$keyword%");
            })->orWhere('status', 'like', "%$keyword%");
        }
        return response()->json($query->get());
    }



    //  Xoá đơn
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }

    public function show($id)
    {
        $order = Order::with([
            'customer',
            'tables', // quan hệ nhiều-nhiều với bảng bàn
            'items.food' // các món ăn trong đơn, nếu có quan hệ food trong OrderItem
        ])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }



    // lấy ra đơn hàng
    public function getOrder()
    {
        $order = Order::with("items")
            ->select("id", "name", "status", "reservation_date", "reservation_time", "total_price")->get();
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


    // Đặt nhiều bàn cho 1 khách hàng, tạo 1 hóa đơn (order) cho các bàn đó
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

        // Lấy các bàn còn trống
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
            // Nếu không đủ bàn, dồn tất cả vào 1 bàn lớn nhất còn trống
            $largestTable = $availableTables->first();
            if ($largestTable) {
                // Tạo order trước
                $order = Order::create([
                    'customer_id' => $request->customer_id,
                    'payment_method' => $request->payment_method,
                    'voucher_id' => $request->voucher_id,
                    'total_price' => $request->total_price,
                    'status' => 'confirmed',
                    'note' => $request->note,
                ]);
                // Sau đó insert order_tables với order_id đã có
                $orderTableIds = [
                    DB::table('order_tables')->insertGetId([
                        'order_id' => $order->id,
                        'table_id' => $largestTable->id,
                        'reservation_date' => $date,
                        'reservation_time' => $time,
                        'status' => 'serve',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                ];
                // Thêm món ăn nếu có
                if ($request->has('foods') && is_array($request->foods)) {
                    foreach ($request->foods as $food) {
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
                }
                // Thêm combo nếu có
                if ($request->has('combos') && is_array($request->combos)) {
                    foreach ($request->combos as $combo) {
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
                }

                return response()->json([
                    'message' => 'Không đủ bàn, đã dồn tất cả khách vào 1 bàn lớn nhất còn trống!',
                    'order_id' => $order->id,
                    'order_table_ids' => $orderTableIds,
                    'selected_tables' => $selectedTables,
                    'ordered_foods' => $request->foods ?? [],
                ]);
            } else {
                return response()->json(['message' => 'Không còn bàn nào trống trong khung giờ này!'], 422);
            }
        }

        // Tạo order
        $order = Order::create([
            'customer_id' => $request->customer_id,
            'payment_method' => $request->payment_method,
            'voucher_id' => $request->voucher_id,
            'total_price' => $request->total_price,
            'status' => 'confirmed',
            'note' => $request->note,
        ]);

        // Tự động tạo payment_code nếu status là confirmed
        if ($order->status === 'confirmed') {
            $order->payment_code = strtoupper(uniqid('PAY'));
            $order->save();
        }

        // Gán bàn vào order_tables
        $orderTableIds = [];
        foreach ($selectedTables as $tableId) {
            $orderTableIds[] = DB::table('order_tables')->insertGetId([
                'order_id' => $order->id,
                'table_id' => $tableId,
                'reservation_date' => $date,
                'reservation_time' => $time,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Thêm món ăn nếu có
        if ($request->has('foods') && is_array($request->foods)) {
            foreach ($request->foods as $food) {
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'food_id' => $food['food_id'],
                    'quantity' => $food['quantity'],
                    'status' => 'pending',
                    'price' => $food['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        // Thêm combo nếu có
        if ($request->has('combos') && is_array($request->combos)) {
            foreach ($request->combos as $combo) {
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'combo_id' => $combo['combo_id'],
                    'quantity' => $combo['quantity'],
                    'status' => 'pending',
                    'price' => $combo['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Sau khi thanh toán thành công, tự tạo payment_code (ví dụ: random chuỗi)
        // Ví dụ: nếu bạn muốn tạo mã khi status là confirmed
        if ($order->status === 'confirmed' && empty($order->payment_code)) {
            $order->payment_code = strtoupper(uniqid('PAY'));
            $order->save();
        }

        return response()->json([
            'message' => 'Đặt bàn thành công',
            'order_id' => $order->id,
            'ids_tables' => $orderTableIds,
            'selected_tables' => $selectedTables,
            'ordered_foods' => $request->foods ?? [],
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
        if ($points >= 5000) {
            return 'Kim Cương';
        } elseif ($points >= 1000) {
            return 'Vàng';
        } elseif ($points >= 100) {
            return 'Bạc';
        } else {
            return 'thành viên';
        }
    }
    // Cập nhật trạng thái
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->status = $request->status ?? 'pending';
        $order->save();
        if ($order->status == 'success') {
            $this->addPoint($order);
            return response()->json(['message' => 'đã tích điểm']);
        }
        return response()->json(['message' => 'Trạng thái đơn hàng đã được cập nhật', 'order' => $order]);
    }
}