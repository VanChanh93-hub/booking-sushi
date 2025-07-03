<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\FoodGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FoodController extends Controller
{
    // Lấy danh sách Food theo category và foodgroup nếu có
    public function index(Request $request)
    {
        $foods = Food::with(['category', 'group'])->get();
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

    // public function getFoodsByCategory($categoryId)
    // {
    //     $foods = Food::where('category_id', $categoryId)
    //         ->where('status', true)
    //         ->with('category')
    //         ->get();
    //                 ->where('status', true)
    //                 ->with(['category', 'group'])
    //                 ->get();

    //     if ($foods->isEmpty()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Không tìm thấy món ăn trong danh mục này'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'data' => $foods
    //     ]);
    // }

    // Tạo món mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'group_id' => 'nullable|exists:food_groups,id',
            'name' => 'required|string|max:255',
            'jpName' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('foods', 'public');
        }
        $food = Food::create($validated);

        return response()->json([
            'message' => 'Food created successfully.',
            'data' => $food->load(['category', 'group'])
        ], 201);
    }

    // Lấy chi tiết món
    public function show($id)
    {
        $food = Food::with(['category', 'group'])->find($id);

        if (!$food) {
            return response()->json(['message' => 'Food not found.'], 404);
        }

        return response()->json($food);
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
            'group_id' => 'nullable|exists:food_groups,id',
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
            'data' => $food->load(['category', 'group'])
        ]);
    }

    // Xoá món ăn

    // Lấy danh sách FoodGroup theo category
    public function getFoodGroupsByCategory($categoryId)
    {
        $groups = FoodGroup::where('category_id', $categoryId)->get();

        return response()->json([
            'data' => $groups
        ]);
    }
    // Lấy danh sách món ăn theo category, nếu có foodgroup thì trả về theo group, nếu không thì trả về theo category
    public function foodsByCategoryWithGroups($categoryId)
    {
        $groups = FoodGroup::where('category_id', $categoryId)->get();

        if ($groups->count() > 0) {
            $result = $groups->map(function ($group) {
                return [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'foods' => $group->food()
                        ->where('status', true)
                        ->with('category')
                        ->get(),
                ];
            });
            return response()->json([
                'type' => 'group',
                'data' => $result
            ]);
        } else {
            // Không có foodgroup, trả về danh sách món ăn theo category
            $foods = Food::where('category_id', $categoryId)
                ->where('status', true)
                ->with('category')
                ->get();
            return response()->json([
                'type' => 'category',
                'data' => $foods
            ]);
        }
    }
    public function updateStatus(Request $request, $id)
    {
        $food = Food::find($id);

        if (!$food) {
            return response()->json(['message' => 'Food not found.'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $food->status = $validated['status'];
        $food->save();

        return response()->json([
            'message' => 'Food status updated successfully.',
            'data' => $food->load(['category', 'group'])
        ]);
    }
}