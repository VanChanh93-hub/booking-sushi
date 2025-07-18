<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Combo;
use App\Models\ComboItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ComboController extends Controller
{
    public function index(Request $request)
{
    $lang = $request->get('lang', 'vi');

    $combos = Combo::with(['comboItems.food'])->get()->map(function ($combo) use ($lang) {
        return [
            'id' => $combo->id,
            'name' => $lang === 'en' ? $combo->name_en : $combo->name,
            'description' => $lang === 'en' ? $combo->description_en : $combo->description,
            'image' => $combo->image ? asset('storage/' . $combo->image) : null,
            'price' => $combo->price,
            'status' => $combo->status,
            'combo_items' => $combo->comboItems->map(function ($item) use ($lang) {
                $food = $item->food;
                if (!$food) return null; // skip nếu food không tồn tại

                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'food' => [
                        'id' => $food->id,
                        'name' => $lang === 'en' ? ($food->name_en ?? $food->name) : $food->name,
                        'jpName' => $food->jp_name,
                        'image' => $food->image ? asset('storage/' . $food->image) : null,
                        'description' => $lang === 'en' ? ($food->description_en ?? $food->description) : $food->description,
                        'price' => $food->price,
                    ],
                ];
            })->filter(), // loại bỏ null nếu có
        ];
    });

    return response()->json($combos);
}

   public function show(Request $request, string $id)
{
    $lang = $request->get('lang', 'vi');
    $combo = Combo::with(['comboItems.food'])->findOrFail($id);

    $result = [
        'id' => $combo->id,
        'name' => $lang === 'en' ? $combo->name_en : $combo->name,
        'description' => $lang === 'en' ? $combo->description_en : $combo->description,
        'image' => $combo->image ? asset('storage/' . $combo->image) : null,
        'price' => $combo->price,
        'status' => $combo->status,
        'combo_items' => $combo->comboItems->map(function ($item) use ($lang) {
            $food = $item->food;
            if (!$food) return null;

            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'food' => [
                    'id' => $food->id,
                    'name' => $lang === 'en' ? ($food->name_en ?? $food->name) : $food->name,
                    'jpName' => $food->jp_name,
                    'image' => $food->image ? asset('storage/' . $food->image) : null,
                    'description' => $lang === 'en' ? ($food->description_en ?? $food->description) : $food->description,
                    'price' => $food->price,
                ],
                ];
        })->filter(),
    ];

    return response()->json($result);
}

    public function updateStatus(Request $request, string $id)
    {
        $combo = Combo::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);
        $combo->status = $validated['status'];
        $combo->save();
        return response()->json(['message' => 'Combo status updated successfully', 'combo' => $combo]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'image' => 'nullable|file|image|max:2048',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price' => 'required|numeric',
            'status' => 'boolean',
            'items' => 'required|array',
            'items.*.food_id' => 'required|exists:foods,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('combos', 'public');
        }

        $combo = Combo::create([
            'name' => $validated['name'],
            'name_en' => $validated['name_en'] ?? $validated['name'],
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'description_en' => $validated['description_en'] ?? $validated['description'],
            'price' => $validated['price'],
            'status' => $validated['status'] ?? true,
        ]);

        foreach ($validated['items'] as $item) {
            ComboItem::create([
                'combo_id' => $combo->id,
                'food_id' => $item['food_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return response()->json(['message' => 'Combo created successfully', 'combo' => $combo], 201);
    }

    public function createComboemp(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'image' => 'nullable|file|image|max:2048',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price' => 'required|numeric',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('combos', 'public');
        }

        $combo = Combo::create([
            'name' => $validated['name'],
            'name_en' => $validated['name_en'] ?? $validated['name'],
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'description_en' => $validated['description_en'] ?? $validated['description'],
            'price' => $validated['price'],
            'status' => true,
        ]);

        return response()->json($combo, 201);
    }

    public function addFoodCombo(Request $request, $combo_id)
    {
        $request->validate([
            'food_id' => 'required|exists:foods,id',
            'quantity' => 'required|integer|min:1',
        ]);

        Combo::findOrFail($combo_id);

        DB::table('combo_items')->insert([
            'combo_id' => $combo_id,
            'food_id' => $request->food_id,
            'quantity' => $request->quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Thêm món ăn vào combo thành công']);
    }

    public function destroyFoodId(Request $request, $combo_id, $food_id)
    {
        Combo::findOrFail($combo_id);
        DB::table('combo_items')
            ->where('combo_id', $combo_id)
            ->where('food_id', $food_id)
            ->delete();

        return response()->json(['message' => 'Món ăn đã được xoá khỏi combo'], 200);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'image' => 'nullable|file|image|max:2048',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price' => 'required|numeric',
            'status' => 'boolean',
            'items' => 'required|array',
            'items.*.food_id' => 'required|exists:foods,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $combo = Combo::findOrFail($id);
        $imagePath = $combo->image;

        if ($request->hasFile('image')) {
            if ($combo->image && Storage::disk('public')->exists($combo->image)) {
                Storage::disk('public')->delete($combo->image);
            }
            $imagePath = $request->file('image')->store('combos', 'public');
        }

        $combo->update([
            'name' => $validated['name'],
            'name_en' => $validated['name_en'] ?? $validated['name'],
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'description_en' => $validated['description_en'] ?? $validated['description'],
            'price' => $validated['price'],
            'status' => $validated['status'] ?? true,
        ]);

        ComboItem::where('combo_id', $combo->id)->delete();

        foreach ($validated['items'] as $item) {
            ComboItem::create([
                'combo_id' => $combo->id,
                'food_id' => $item['food_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return response()->json(['message' => 'Combo updated successfully', 'combo' => $combo]);
    }

    public function destroy(string $id)
    {
        // Optional: implement if needed
    }
}
