<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodGroup;

class FoodgroupController extends Controller
{
    // Lấy danh sách nhóm món ăn
    public function index()
    {
        $groups = FoodGroup::with('category')->get();
        return response()->json($groups);
    }

    // Tạo mới nhóm món ăn
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
        ]);
        $group = FoodGroup::create($validated);
        return response()->json($group, 201);
    }

    // Lấy chi tiết nhóm món ăn
    public function show($id)
    {
        $group = FoodGroup::with('category', 'food')->find($id);
        if (!$group) {
            return response()->json(['message' => 'Không tìm thấy nhóm món ăn'], 404);
        }
        return response()->json($group);
    }

    // Cập nhật nhóm món ăn
    public function update(Request $request, $id)
    {
        $group = FoodGroup::find($id);
        if (!$group) {
            return response()->json(['message' => 'Không tìm thấy nhóm món ăn'], 404);
        }
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'name_en' => 'nullable|string|max:255',
        ]);
        $group->update($validated);
        return response()->json($group);
    }
    public function updateStatus(Request $request, $id)
    {
        $group = FoodGroup::find($id);
        if (!$group) {
            return response()->json(['message' => 'Không tìm thấy nhóm món ăn'], 404);
        }
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);
        $group->update($validated);
        return response()->json($group);
    }

}
