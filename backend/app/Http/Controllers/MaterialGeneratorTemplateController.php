<?php

namespace App\Http\Controllers;

use App\Models\MaterialGeneratorTemplate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MaterialGeneratorTemplateController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('in_group'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $templates = MaterialGeneratorTemplate::where('mobilizon_group_id', $request->mobilizon_group_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $templates
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mobilizon_group_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'material_type' => 'required|in:event,eventList',
            'dimension' => 'required|string|max:50',
            'global_settings' => 'required|array',
            'objects_data' => 'required|array',
        ]);

        $template = MaterialGeneratorTemplate::create($validated);

        return response()->json([
            'data' => $template
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MaterialGeneratorTemplate $materialGeneratorTemplate)
    {
        return response()->json([
            'data' => $materialGeneratorTemplate
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaterialGeneratorTemplate $materialGeneratorTemplate)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'global_settings' => 'sometimes|array',
            'objects_data' => 'sometimes|array',
        ]);

        $materialGeneratorTemplate->update($validated);

        return response()->json([
            'data' => $materialGeneratorTemplate
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaterialGeneratorTemplate $materialGeneratorTemplate)
    {
        $materialGeneratorTemplate->delete();

        return response()->json([
            'message' => 'Template wurde gelöscht'
        ]);
    }
}
