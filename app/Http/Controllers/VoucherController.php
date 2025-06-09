<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Voucher::query();

        if ($request->has('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        return response()->json($query->get());
    }



    public function store(Request $request)
    {
        $vailidated = $request->validate([
            'code' => 'required|string|max:255|unique:vouchers,code',
            'usage_limit' => 'required|integer|min:1',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'required_points' => 'nullable|integer|min:0',
        ]);
        $voucher = Voucher::create($vailidated);
        return response()->json([
            'message' => 'tạo thành công',
            'data' => $voucher
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return response()->json(['message' => 'không tồn tại'], 404);
        }
        return response()->json($voucher, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Voucher $voucher) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,  $id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return response()->json(['message' => 'không tồn tại'], 404);
        }
        $vailidated = $request->validate([
            'code' => 'required|string|max:255|unique:vouchers,code,',
            'usage_limit' => 'required|integer|min:1',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'required_points' => 'nullable|integer',
        ]);
        $voucher->update($vailidated);
        return response()->json([
            'message' => 'cập nhật thành công',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return response()->json([
            'message' => 'Xóa thành công',
        ], 200);
    }
    public function applyVoucher(Request  $request)
    {
        $total = $request->total;
        $voucherCode = $request->code;
        $voucher = Voucher::where('code', $voucherCode)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
        if (!$voucher) {
            return response()->json(['message' => 'voucher không tồn tại'], 404);
        }
        if ($voucher->usage_limit <= 0) {
            return response()->json(['message' => 'Voucher đã hết lượt sử dụng'], 400);
        }

        $voucher->usage_limit -= 1;
        $voucher->save();
        $discount = $voucher->discount_value;
        $newTotal = max(0, $total - $discount);

        return response()->json([
            'message' => 'Voucher áp dụng thành công',
            'new_total' => $newTotal,
        ]);
    }
}