<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Combo;
use App\Models\ComboItem;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $combo = Combo::with(['comboItems.food'])->findOrFail($id);
        return response()->json($combo);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
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
