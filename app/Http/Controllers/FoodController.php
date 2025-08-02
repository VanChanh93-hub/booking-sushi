<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\FoodGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FoodController extends Controller
{
    // Đã loại bỏ $usdExchangeRate và formatPriceForLang vì việc chuyển đổi sẽ được thực hiện ở frontend

    public function index(Request $request)
    {
        $lang = $request->get('lang', 'vi');
        $foods = Food::with(['category', 'group'])->get();

        $localized = $foods->map(function ($food) use ($lang) {
            return [
                'id' => $food->id,
                'category_id' => $food->category_id,
                'group_id' => $food->group_id,
                // Luôn bao gồm name_en và description_en trong phản hồi
                'name' => $lang === 'en' ? ($food->name_en ?? $food->name) : $food->name,
                'name_en' => $food->name_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
                'jpName' => $food->jpName,
                'description' => $lang === 'en' ? ($food->description_en ?? $food->description) : $food->description,
                'description_en' => $food->description_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
                'price' => (float) $food->price, // Luôn trả về giá gốc VND
                'status' => $food->status,
                'image' => $food->image ? asset('storage/' . $food->image) : null,
                'category' => $food->category,
                'group' => $food->group,
            ];
        });

        return response()->json(['data' => $localized]);
    }

    public function getFoodsByCategory(Request $request, $categoryId)
    {
        $lang = $request->get('lang', 'vi');
        $foods = Food::where('category_id', $categoryId)->where('status', true)->with(['category', 'group'])->get();

        if ($foods->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy món ăn trong danh mục này'], 404);
        }

        $localized = $foods->map(function ($food) use ($lang) {
            return [
                'id' => $food->id,
                // Luôn bao gồm name_en và description_en trong phản hồi
                'name' => $lang === 'en' ? ($food->name_en ?? $food->name) : $food->name,
                'name_en' => $food->name_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
                'jpName' => $food->jpName,
                'description' => $lang === 'en' ? ($food->description_en ?? $food->description) : $food->description,
                'description_en' => $food->description_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
                'price' => (float) $food->price, // Luôn trả về giá gốc VND
                'status' => $food->status,
'image' => $food->image ? asset('storage/' . $food->image) : null,
                'category' => $food->category,
                'group' => $food->group,
            ];
        });

        return response()->json(['data' => $localized]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'group_id' => 'nullable|exists:food_groups,id',
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'jpName' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('foods', 'public');
        }

        $food = Food::create($validated);
        return response()->json(['message' => 'Food created successfully.', 'data' => $food->load(['category', 'group'])], 201);
    }

    public function show(Request $request, $id)
    {
        $lang = $request->get('lang', 'vi');
        $food = Food::with(['category', 'group'])->find($id);

        if (!$food) {
            return response()->json(['message' => 'Food not found.'], 404);
        }

        return response()->json([
            'id' => $food->id,
            // Luôn bao gồm name_en và description_en trong phản hồi
            'name' => $lang === 'en' ? ($food->name_en ?? $food->name) : $food->name,
            'name_en' => $food->name_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
            'jpName' => $food->jpName,
            'description' => $lang === 'en' ? ($food->description_en ?? $food->description) : $food->description,
            'description_en' => $food->description_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
            'price' => (float) $food->price, // Luôn trả về giá gốc VND
            'status' => $food->status,
            'image' => $food->image ? asset('storage/' . $food->image) : null,
            'category' => $food->category,
            'group' => $food->group,
        ]);
    }

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
            'name_en' => 'nullable|string|max:255',
            'jpName' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            // 'season' => 'sometimes|in:spring,summer,autumn,winter',
        ]);

        if ($request->hasFile('image')) {
            if ($food->image) {
                Storage::disk('public')->delete($food->image);
            }
            $validated['image'] = $request->file('image')->store('foods', 'public');
        }

        $food->update($validated);
        return response()->json(['message' => 'Food updated successfully.', 'data' => $food->load(['category', 'group'])]);
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

        return response()->json(['message' => 'Food status updated successfully.', 'data' => $food->load(['category', 'group'])]);
    }

    public function getFoodGroupsByCategory($categoryId)
    {
        $groups = FoodGroup::where('category_id', $categoryId)->get();
        return response()->json(['data' => $groups]);
    }

    public function foodsByCategoryWithGroups(Request $request, $categoryId)
    {
        $lang = $request->get('lang', 'vi');
        $groups = FoodGroup::where('category_id', $categoryId)->get();

        if ($groups->count() > 0) {
            $result = $groups->map(function ($group) use ($lang) {
                $foods = $group->food()->where('status', true)->with('category')->get()->map(function ($food) use ($lang) {
                    return [
                        'id' => $food->id,
                        // Luôn bao gồm name_en và description_en trong phản hồi
                        'name' => $lang === 'en' ? ($food->name_en ?? $food->name) : $food->name,
                        'name_en' => $food->name_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
                        'jpName' => $food->jpName,
                        'description' => $lang === 'en' ? ($food->description_en ?? $food->description) : $food->description,
                        'description_en' => $food->description_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
                        'price' => (float) $food->price, // Luôn trả về giá gốc VND
                        'status' => $food->status,
                        'image' => $food->image ? asset('storage/' . $food->image) : null,
                        'category' => $food->category,
                    ];
                });
                return [
                    'group_id' => $group->id,
                    // THAY ĐỔI QUAN TRỌNG: Áp dụng logic dịch cho tên nhóm
                    'group_name' => $lang === 'en' ? ($group->name_en ?? $group->name) : $group->name,
                    'group_name_en' => $group->name_en ?? '', // Đảm bảo luôn có trường này
                    'foods' => $foods,
                ];
            });
            return response()->json(['type' => 'group', 'data' => $result]);
        } else {
            $foods = Food::where('category_id', $categoryId)->where('status', true)->with('category')->get()->map(function ($food) use ($lang) {
                return [
                    'id' => $food->id,
                    // Luôn bao gồm name_en và description_en trong phản hồi
                    'name' => $lang === 'en' ? ($food->name_en ?? $food->name) : $food->name,
                    'name_en' => $food->name_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
                    'jpName' => $food->jpName,
                    'description' => $lang === 'en' ? ($food->description_en ?? $food->description) : $food->description,
                    'description_en' => $food->description_en ?? '', // Đảm bảo luôn có trường này, nếu null thì trả về chuỗi rỗng
                    'price' => (float) $food->price, // Luôn trả về giá gốc VND
                    'status' => $food->status,
                    'image' => $food->image ? asset('storage/' . $food->image) : null,
                    'category' => $food->category,
                ];
            });
            return response()->json(['type' => 'category', 'data' => $foods]);
        }
    }
}
