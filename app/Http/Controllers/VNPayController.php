<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VNPayController extends Controller
{
    public function createurlvnpay(Request $request)
    {
        $order = Order::findOrFail($request->order_id);

        // ✅ Lấy số tiền thanh toán từ frontend gửi lên
        $amount = (int) $request->input('amount', 0);

        if ($amount < 5000 || $amount > 1000000000) {
            return response()->json([
                'message' => 'Số tiền không hợp lệ (tối thiểu 5.000đ, tối đa 1 tỷ)',
            ], 422);
        }

        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_Url = env('VNPAY_URL');
        $vnp_ReturnUrl = env('VNPAY_RETURN_URL', 'http://127.0.0.1:8000/vnpay-return');

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $amount * 100, // ✅ nhân 100 để quy đổi sang đơn vị VNPay
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Thanh toán đơn hàng #" . $order->id,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => str_pad($order->id, 8, "0", STR_PAD_LEFT),
        );

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";

        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        return response()->json([
            'message' => 'success',
            'data' => $vnp_Url
        ]);
    }

    public function vnpayReturn(Request $request)
    {
        $order = Order::where('id', intval($request->vnp_TxnRef))->first();

        if ($request->vnp_ResponseCode == "00") {
            $payment_code = strtoupper(uniqid('PAY'));
            $order->update([
                'payment_status' => 'done',
                'status' => 'confirmed',
                'payment_code' => $payment_code,
            ]);

            return redirect('http://localhost:3000/payment-success');
        } else {
            // ❌ Thanh toán thất bại → xóa order và các liên kết
            if ($order) {
                $order->items()->delete();
$order->tables()->detach();
                $order->delete();
            }
            return redirect('http://localhost:3000/payment-failed');
        }
    }
}
