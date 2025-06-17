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
    //  Danh sách đơn
    // Lấy danh sách đơn hàng (chỉ trả về danh sách, không kèm chi tiết)
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

        // Lấy danh sách đơn hàng, có thể kèm customer và tables nếu muốn
        $orders = $query->with('customer', 'tables')->latest()->get();

        return response()->json($orders);
    }

    //  Xoá đơn
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }

    // Lấy chi tiết đơn hàng (kèm các quan hệ liên quan)
    public function show($id)
    {
        $order = Order::with([
            'customer',
            'tables',
            'items.food',
            'items.combo'
        ])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }



    // lấy ra đơn hàng

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

            // Chuẩn bị dữ liệu thanh toán trả về FE
            $paymentData = [
                'order_id' => $order->id,
                'amount' => $order->total_price,
                'payment_method' => $order->payment_method,
                'payment_code' => $order->payment_code,
                'status' => $order->status,
                // Có thể bổ sung các trường khác nếu cần
            ];

            return response()->json([
                'message' => 'Không đủ bàn, đã dồn tất cả khách vào 1 bàn lớn nhất còn trống!',
                'order_id' => $order->id,
                'order_table_ids' => $orderTableIds,
                'selected_tables' => $selectedTables,
                'ordered_foods' => $request->foods ?? [],
                'payment' => $paymentData, // Thông tin thanh toán gửi FE
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
        'payment_status' => 'pending',
        'status' => 'pending',
        'note' => $request->note,
    ]);

    // Tự động tạo payment_code nếu status là confirmed


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



    // Chuẩn bị dữ liệu thanh toán trả về FE
    $paymentData = [
        'order_id' => $order->id,
        'amount' => $order->total_price,
        'payment_method' => $order->payment_method,
        'status' => $order->status,
        'payment_status' => $order->payment_status,
        // Có thể bổ sung các trường khác nếu cần
    ];
    return response()->json([
        'message' => 'Đặt bàn thành công',
        'order_id' => $order->id,
        'ids_tables' => $orderTableIds,
        'selected_tables' => $selectedTables,
        'ordered_foods' => $request->foods ?? [],
        'payment' => $paymentData, // Thông tin thanh toán gửi FE
    ]);
   }

    public function addPoint($order)
    {
        $pointsEarned = floor($order->total_price / 10000);
        $customer = Customer::find($order->customer_id);
        if (!$customer) {
            return response()->json(['message' => 'Khách hàng không tồn tại'], 404);
        }
        $customer->point += $pointsEarned;
        $customer->membership_level = $this->calculateMembershipLevel($customer->point);
        $customer->save();
        return response()->json([
            'message' => 'Điểm thưởng đã được cộng',
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

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,success,cancelled'
        ]);
        $order->status = $validated['status'];
        if ($order->status == 'success') {
            $order->save();
            $this->addPoint($order);
            return response()->json(['message' => 'Đã tích điểm', 'order' => $order]);
        }
        $order->save();
        return response()->json(['message' => 'Trạng thái đơn hàng đã được cập nhật', 'order' => $order]);
    }

}
