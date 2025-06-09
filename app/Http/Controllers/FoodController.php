<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FoodController extends Controller
{
    // Lấy danh sách Food
    public function index(Request $request)
    {
        $query = Food::get();

        return response()->json([
            'data' => $query
        ]);
    }


    public function getFoodsByCategory($categoryId)
    {
        $foods = Food::where('category_id', $categoryId)
            ->where('status', true)
            ->with('category')
            ->get();

        if ($foods->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy món ăn trong danh mục này'
            ], 404);
        }

        return response()->json([
            'data' => $foods
        ]);
    }

    // Thêm món mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'jpName' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('foods', 'public');
        }
        $food = Food::create($validated);

        return response()->json([
            'message' => 'Food created successfully.',
            'data' => $food->load('category')
        ], 201);
    }

    // Lấy chi tiết món
    public function show($id)
    {
        $Food = Food::with('category')->find($id);

        if (!$Food) {
            return response()->json(['message' => 'Food not found.'], 404);
        }

        return response()->json($Food);
    }

    // Sửa món ăn
    public function update(Request $request, $id)
    {
        $food = Food::find($id);

        if (!$food) {
            return response()->json(['message' => 'Food not found.'], 404);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'jpName' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($food->image) {
                Storage::disk('public')->delete($food->image);
            }
            $validated['image'] = $request->file('image')->store('foods', 'public');
        }

        $food->update($validated);

        return response()->json([
            'message' => 'Food updated successfully.',
            'data' => $food->load('category')
        ]);
    }

    // Xoá món ăn
    public function destroy($id)
    {
        $food = Food::find($id);

        if (!$food) {
            return response()->json(['message' => 'Food not found.'], 404);
        }

        if ($food->image) {
            Storage::disk('public')->delete($food->image);
        }

        $food->delete();

        return response()->json(['message' => 'Food deleted successfully.']);
    }
    // tỏng món ăn

}