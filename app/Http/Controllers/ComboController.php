<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Combo;
use App\Models\ComboItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
class ComboController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $combos = Combo::with(['comboItems.food'])->get();
        return response()->json($combos);
    }

    /**
     * Show the form for creating a new resource.
     */
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
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|image|max:2048',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'status' => 'boolean',
            'items' => 'required|array',
            'items.*.food_id' => 'required|exists:foods,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Xử lý upload ảnh nếu có
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('combos', 'public');
        }

        // Create combo
        $combo = Combo::create([
            'name' => $validated['name'],
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'status' => $validated['status'] ?? true,
        ]);

        // Add combo items
        foreach ($validated['items'] as $item) {
            ComboItem::create([
                'combo_id' => $combo->id,
                'food_id' => $item['food_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return response()->json(['message' => 'Combo created successfully', 'combo' => $combo], 201);
    }
    public function createComboemp(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|image|max:2048',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
        ]);
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('combos', 'public');
        }
        $combo = Combo::create([
            'name' => $validated['name'],
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'status' => true, // Mặc định trạng thái là true
        ]);
        return response()->json($combo, 201);
    }
/**
 * Adds a food item to an existing combo.
 *
 * This function validates the input request to ensure the food item exists
 * and the quantity is a positive integer. It then checks if the specified
 * combo exists and adds the food item to the combo in the database.
 *
 * @param Request $request The incoming request containing 'food_id' and 'quantity'.
 * @param int $combo_id The ID of the combo to which the food item will be added.
 * @return \Illuminate\Http\JsonResponse A JSON response indicating success.
 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the combo does not exist.
 */

    public function addFoodCombo(Request $request, $combo_id)
    {
        $request->validate([
            'food_id' => 'required|exists:foods,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Kiểm tra combo_id có tồn tại không
        $combo = Combo::findOrFail($combo_id);

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
        $combo = Combo::findOrFail($combo_id);
        DB::table('combo_items')
            ->where('combo_id', $combo_id)
            ->where('food_id', $food_id)
            ->delete();

        return response()->json(['message' => 'Món ăn đã được xoá khỏi combo'], 200);
    }
    public function show(string $id)
    {
        $combo = Combo::with(['comboItems.food'])->findOrFail($id);
        return response()->json($combo);
    }

    public function update(Request $request, string $id)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|image|max:2048',
            'description' => 'nullable|string',
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

        // Update combo
        $combo->update([
            'name' => $validated['name'],
            'image' => $imagePath,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'status' => $validated['status'] ?? true,
        ]);

        // Remove old items
        ComboItem::where('combo_id', $combo->id)->delete();

        // Add new items
        foreach ($validated['items'] as $item) {
            ComboItem::create([
                'combo_id' => $combo->id,
                'food_id' => $item['food_id'],
                'quantity' => $item['quantity'],
            ]);
        }
        return response()->json(['message' => 'Combo updated successfully', 'combo' => $combo]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
