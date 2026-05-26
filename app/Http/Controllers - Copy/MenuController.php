<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Menu;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Menus/Index', [
            'menus' => Menu::all(),
            'categories' => Category::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:191',
            'price'       => 'required|numeric|min:0',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'is_available'=> 'nullable',
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


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // 1️⃣ Validate
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'category_id'   => 'required|exists:categories,id',
            'price'         => 'required|numeric|min:0',
            'is_available'  => 'required|boolean',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // 2️⃣ Find menu
        $menu = Menu::findOrFail($id);

        // 3️⃣ Handle image upload
        if ($request->hasFile('image')) {

            // delete old image if exists
            if ($menu->image_path && Storage::disk('public')->exists($menu->image_path)) {
                Storage::disk('public')->delete($menu->image_path);
            }

            // store new image
            $path = $request->file('image')->store('menus', 'public');

            $validated['image_path'] = $path;
        }

        // 4️⃣ Update menu
        $menu->update($validated);

        // 5️⃣ (Optional) Log success
        Log::info('Menu updated successfully', [
            'menu_id' => $menu->id,
            'data'    => $validated,
        ]);

        // 6️⃣ Redirect back for Inertia
        return redirect()->back()->with('success', 'Menu updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
