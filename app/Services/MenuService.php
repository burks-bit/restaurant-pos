<?php

namespace App\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Menu;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MenuService
{
    public function index()
    {
        return Inertia::render('Menus/Index', [
            'menus' => Menu::all(),
            'categories' => Category::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:191',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'is_available' => 'nullable',
        ]);

        $validated['is_available'] = $request->boolean('is_available');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('menus', 'public');
            $validated['image_path'] = $path;
            $validated['image'] = basename($path);
        }

        $menu = Menu::create($validated);

        return redirect()->route('admin.menus')
            ->with('success', 'Menu "' . $menu->name . '" added successfully!');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'is_available' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $menu = Menu::findOrFail($id);

        if ($request->hasFile('image')) {
            if ($menu->image_path && Storage::disk('public')->exists($menu->image_path)) {
                Storage::disk('public')->delete($menu->image_path);
            }

            $path = $request->file('image')->store('menus', 'public');
            $validated['image_path'] = $path;
        }

        $menu->update($validated);

        Log::info('Menu updated successfully', [
            'menu_id' => $menu->id,
            'data' => $validated,
        ]);

        return redirect()->back()->with('success', 'Menu updated successfully.');
    }
}