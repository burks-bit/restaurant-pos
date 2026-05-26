<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $ingredients = Ingredient::when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('quantity', 'asc')
            ->get();

        return Inertia::render('Inventory/Index', [
            'ingredients' => $ingredients,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    // Stock IN (increase quantity)
    public function stockIn(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:1',
            'note' => 'nullable|string',
        ]);

        $ingredient = Ingredient::findOrFail($id);

        // Increase stock
        $ingredient->increment('quantity', $request->quantity);

        // Record movement
        InventoryMovement::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'in',
            'quantity' => $request->quantity,
            'note' => $request->note,
        ]);

        return back();
    }

    // Stock OUT (decrease quantity)
    public function stockOut(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:1',
            'note' => 'nullable|string',
        ]);

        $ingredient = Ingredient::findOrFail($id);

        if ($ingredient->quantity < $request->quantity) {
            return back()->withErrors(['quantity' => 'Not enough stock.']);
        }

        // Decrease stock
        $ingredient->decrement('quantity', $request->quantity);

        // Record movement
        InventoryMovement::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'out',
            'quantity' => $request->quantity,
            'note' => $request->note,
        ]);

        return back();
    }
}
