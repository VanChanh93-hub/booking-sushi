<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\feedback;

class feedbackController extends Controller
{

    public function index()
    {
        // Trả về trang feedback
        return response()->json(feedback::all());
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
            'customer_id' => 'required|integer',
        ]);
        if ($validated['rating'] < 1 || $validated['rating'] > 5) {
            return response()->json(['message' => 'Đánh giá phải từ 1 đến 5'], 422);
        }
        // ktra đã có feedback cho order_id và customer_id chưa
        $existingFeedback = feedback::where('order_id', $validated['order_id'])
            ->where('customer_id', $validated['customer_id'])
            ->first();
        if ($existingFeedback) {
            return response()->json(['message' => 'Bạnfeedback cho đơn hàng này rồi'], 422);
        }
        $feedback = feedback::create($validated);

        return response()->json([
            'message' => 'thành công',
            'data' => $feedback,
        ], 201);
    }
    public function show($id)
    {
        $feedback = feedback::find($id);
        if (!$feedback) {
            return response()->json(['message' => 'Feedback không tồn tại'], 404);
        }
        return response()->json([
            'message' => 'Thông tin feedback',
            'data' => $feedback
        ]);
    }

    public function getFeedbackByOrderId($orderId)
    {
        $feedbacks = feedback::where('order_id', $orderId)->get();
        if ($feedbacks->isEmpty()) {
            return response()->json(['message' => 'Không có feedback cho đơn hàng này'], 404);
        }
        return response()->json([
            'message' => 'Danh sách feedback cho đơn hàng',
            'data' => $feedbacks
        ]);
    }
    public function destroy($id)
    {
        $feedback = feedback::find($id);
        if (!$feedback) {
            return response()->json(['message' => 'Feedback không tồn tại'], 404);
        }
        $feedback->delete();
        return response()->json(['message' => 'Xóa feedback thành công']);
    }

    public function getFeedbackByCustomerId($customerId)
    {
        $feedbacks = feedback::where('customer_id', $customerId)->get();
        if ($feedbacks->isEmpty()) {
            return response()->json(['message' => 'Không có feedback nào cho khách hàng này'], 404);
        }
        return response()->json([
            'message' => 'Danh sách feedback của khách hàng',
            'data' => $feedbacks
        ]);
    }
}