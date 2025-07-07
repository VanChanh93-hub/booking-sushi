<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CustomerVoucher;
use App\Models\Customer;
use App\Models\Voucher;


class CustomerVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {

        $voucher = CustomerVoucher::where('customer_id', $id)
            ->with(['voucher'])
            ->get();
        $vouchers = $voucher->map(function ($item) {
            return [
                'id' => $item->voucher->id,
                'code' => $item->voucher->code,
                'discount_value' => $item->voucher->discount_value,
                'start_date' => $item->voucher->start_date,
                'end_date' => $item->voucher->end_date,
                'status' => $item->voucher->status,
            ];
        });
        if ($vouchers->isEmpty()) {
            return response()->json(['message' => 'Không có voucher nào'], 404);
        }
        return response()->json([
            'message' => 'Danh sách voucher của khách hàng',
            'data' => $vouchers
        ]);
    }



    public function exchangePoints(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $voucher = Voucher::findOrFail($request->voucher_id);
        // check cus có voucher đó chưa
        $exists = CustomerVoucher::where('customer_id', $customer->id)
            ->where('voucher_id', $voucher->id)
            ->exists();

        if ($exists) {
            return response()->json(["message" => "Bạn đã đổi voucher này rồi"], 400);
        }
        if (!$voucher->is_personal) {
            return response()->json(["message" => "Voucher này không phải là voucher cá nhân"], 400);
        }
        // check điểm của cus
        if (is_null($voucher->required_points) || $customer->point < $voucher->required_points) {
            $requiredPoints = $voucher->required_points - $customer->point;
            return response()->json(["message" => "Bạn không đủ điểm để đổi voucher, bạn cần thêm " . $requiredPoints . ""], 400);
        }

        if ($voucher->used >= $voucher->usage_limit) {
            return response()->json(["message" => "Voucher này đã hết lượt sử dụng"], 400);
        }

        $customer->point -= $voucher->required_points;
        $customer->save();

        $voucher->used += 1;
        $voucher->save();

        CustomerVoucher::create([
            'customer_id' => $customer->id,
            'voucher_id' => $voucher->id,
            'assigned_at' => now(),
            'date' => $voucher->end_date,
            'is_used' => 0,
        ]);

        return response()->json([
            "message" => "Đổi voucher thành công",
            "voucher_info" => CustomerVoucher::where("customer_id", $customer->id)->get()
        ]);
    }



    public function applyVoucher(Request $request)
{
    $request->validate([
        'customer' => 'required|exists:customers,id',
        'total' => 'required|numeric|min:0',
        'code' => 'required|string'
    ]);

    $customerId = $request->customer;
    $total = $request->total;
    $voucherCode = $request->code;

    $voucher = Voucher::where('code', $voucherCode)
        ->where('status', 'active')
        ->where('start_date', '<=', now())
        ->where('end_date', '>=', now())
        ->first();

    if (!$voucher) {
        return response()->json(['message' => 'Voucher không tồn tại hoặc đã hết hạn'], 404);
    }

    // Kiểm tra điều kiện áp dụng
    if ($voucher->required_total && $total < $voucher->required_total) {
        return response()->json(['message' => 'Đơn hàng không đủ điều kiện để áp dụng voucher'], 400);
    }

    $customerVoucher = CustomerVoucher::where("customer_id", $customerId)
        ->where("voucher_id", $voucher->id)
        ->first();

    if ($customerVoucher) {
        // Áp dụng voucher cá nhân
        if ($customerVoucher->is_used) {
            return response()->json(['message' => 'Bạn đã sử dụng voucher này rồi'], 400);
        }

        $customerVoucher->is_used = 1;
        $customerVoucher->save();
    } else {
        // Áp dụng voucher dùng chung
        if ($voucher->used >= $voucher->usage_limit) {
            return response()->json(['message' => 'Voucher đã hết lượt sử dụng'], 400);
        }

        $voucher->used += 1;
        $voucher->usage_limit -= 1;
        $voucher->save();
    }

    // Tính giá sau khi giảm
    $discount = $voucher->discount_value;
    $newTotal = max(0, $total - $discount);

    return response()->json([
        'message' => 'Áp dụng voucher thành công',
        'discount' => $discount,
        'new_total' => $newTotal,
        'voucher' => [
            'id' => $voucher->id,
            'code' => $voucher->code
        ]
    ]);
}
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $customerId = $request->customer_id;
        $voucherId = $request->voucher_id;
        $customer = Customer::findOrFail($customerId);

        if ($customer->point_available < 50) {
            return response()->json(['message' => 'Khách hàng không đủ điểm để đổi voucher'], 400);
        }

        if (is_null($voucherId)) {
            $customer->point_available -= 50;
            $customer->save();
            return response()->json(['message' => 'chúc bạn may mắn lần sau'], 200);
        }
        $voucher  = Voucher::where('id', $voucherId)->where("is_personal", 1)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('usage_limit', '>', 0)
            ->first();;
        if (!$voucher) {
            return response()->json(['message' => 'Voucher không tồn tại hoặc không phải là voucher cá nhân'], 404);
        }

        $now = now();
        CustomerVoucher::create([
            'customer_id' => $customerId,
            'voucher_id' => $voucherId,
            'assigned_at' => now(),
            'date' =>  $voucher->end_date,
        ]);
        $customer->point_available -= 50;
        $customer->save();


        return response()->json([
            'message' => 'Voucher đã được thêm thành công',
            'customer_id' => $customerId,
            'voucher_id' => $voucherId,
            'assigned_at' => now(),
            'date' => $voucher->end_date,
        ]);
    }
}
