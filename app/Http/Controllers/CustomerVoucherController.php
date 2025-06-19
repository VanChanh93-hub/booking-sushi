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

        $exists = CustomerVoucher::where('customer_id', $customer->id)
            ->where('voucher_id', $voucher->id)
            ->exists();

        if ($exists) {
            return response()->json(["message" => "Bạn đã đổi voucher này rồi"]);
        }

        if ($customer->point < $voucher->required_points) {
            return response()->json(["message" => "Bạn không đủ điểm để đổi"]);
        }

        if ($voucher->usage_limit < 1) {
            return response()->json(["message" => "Voucher này đã hết"]);
        }

        $customer->point -= $voucher->required_points;
        $customer->save();

        $voucher->usage_limit -= 1;
        $voucher->save();

        // Tạo voucher cá nhân
        CustomerVoucher::create([
            'customer_id' => $customer->id,
            'voucher_id' => $voucher->id,
            'assigned_at' => now(),
            'date' => $voucher->end_date,
            'is_used' => 0
        ]);

        return response()->json([
            "message" => "Đã đổi voucher thành công",
            "voucher_info" => CustomerVoucher::where("customer_id", $customer->id)->get()
        ]);
    }


    public function applyVoucher(Request $request)
    {
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

        if ($voucher->is_personal) {
            $customerVoucher = CustomerVoucher::where("customer_id", $customerId)
                ->where("voucher_id", $voucher->id)
                ->first();

            if (!$customerVoucher) {
                return response()->json(['message' => 'Bạn chưa đổi voucher này', "chekc" => $customerId], 403);
            }

            if ($customerVoucher->is_used) {
                return response()->json(['message' => 'Bạn đã sử dụng voucher này rồi'], 400);
            }

            $customerVoucher->is_used = 1;
            $customerVoucher->save();
        } else {
            // Voucher dùng chung
            if ($voucher->usage_limit <= 0) {
                return response()->json(['message' => 'Voucher đã hết lượt sử dụng', "vheck" => $voucher->usage_limit], 400);
            }

            $voucher->usage_limit -= 1;
            $voucher->save();
        }

        $discount = $voucher->discount_value;
        $newTotal = max(0, $total - $discount);

        return response()->json([
            'message' => 'Voucher áp dụng thành công',
            'new_total' => $newTotal,
        ]);
    }
}