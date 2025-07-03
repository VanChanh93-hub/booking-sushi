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
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'required|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'title' => 'nullable|string',
            'content' => 'nullable|string',
        ]);

        // Kiểm tra feedback đã tồn tại chưa
        $existingFeedback = Feedback::where('order_id', $validated['order_id'])
            ->where('customer_id', $validated['customer_id'])
            ->first();

        if ($existingFeedback) {
            return response()->json(['message' => 'Bạn đã gửi đánh giá cho đơn hàng này rồi'], 422);
        }

        $feedback = Feedback::create($validated);

        return response()->json([
            'message' => 'Gửi đánh giá thành công',
            'data' => $feedback,
        ], 201);
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