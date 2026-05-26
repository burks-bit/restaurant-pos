<?php

namespace App\Services;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Configuration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ConfigurationService
{
    public function index(): Response
    {
        return Inertia::render('Configurations/Index', [
            'configurations' => Configuration::get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:configurations,name',
            'description' => 'nullable|string|max:500',
            'value' => 'nullable|string|max:255',
            'status' => 'required|in:0,1',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:9168',
        ]);

        $configuration = new Configuration();
        $configuration->name = $request->name;
        $configuration->description = $request->description;
        $configuration->value = $request->value;
        $configuration->status = $request->status;

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('web_images', 'public');
            $configuration->file_path = $path;
        }

        $configuration->save();

        return redirect()->back()->with('success', 'Configuration created.');
    }

    public function update(Request $request, Configuration $configuration)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:configurations,name,' . $configuration->id,
            'description' => 'nullable|string|max:500',
            'value' => 'nullable|string|max:255',
            'status' => 'required|in:0,1',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:9168',
        ]);

        $configuration->name = $request->name;
        $configuration->description = $request->description;
        $configuration->value = $request->value;
        $configuration->status = $request->status;

        if ($request->hasFile('file')) {
            if ($configuration->file_path && Storage::disk('public')->exists($configuration->file_path)) {
                Storage::disk('public')->delete($configuration->file_path);
            }

            $path = $request->file('file')->store('web_images', 'public');
            $configuration->file_path = $path;
        }

        $configuration->save();

        return redirect()->back()->with('success', 'Configuration updated.');
    }
}