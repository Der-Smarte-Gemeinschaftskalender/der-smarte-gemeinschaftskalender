<?php

namespace App\Http\Controllers;

use App\Models\MaterialGeneratorValue;
use App\Models\Mobilizon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Storage;

class MaterialGeneratorValueController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            'auth',
            new Middleware('in_group', only: ['upsert']),
        ];
    }
    public function upsert(Request $request)
    {
        $generatorValues = MaterialGeneratorValue::where('mobilizon_group_id', $request->input('mobilizon_group_id'))->first();
        $preferredUsername = $request->input('mobilizon_preferredusername');
        if (!$generatorValues) {
            $generatorValues = new MaterialGeneratorValue();
        }
        $generatorValues->mobilizon_preferredusername = $preferredUsername;
        $generatorValues->mobilizon_group_id = $request->input('mobilizon_group_id');
        $generatorValues->default_text_settings = $request->input('default_text_settings');
        $generatorValues->default_header_settings = $request->input('default_header_settings');
        $generatorValues->save();
        if ($request->hasFile('eventListStoryImage')) {
            Storage::disk('public')->putFileAs('/' . $preferredUsername, $request->file('eventListStoryImage'),  'eventListStory.png');
        }
        if ($request->hasFile('eventListPostImage')) {
            Storage::disk('public')->putFileAs('/' . $preferredUsername, $request->file('eventListPostImage'),  'eventListPost.png');
        }
        if ($request->hasFile('eventStoryImage')) {
            Storage::disk('public')->putFileAs('/' . $preferredUsername, $request->file('eventStoryImage'),  'eventStory.png');
        }
        if ($request->hasFile('eventPostImage')) {
            Storage::disk('public')->putFileAs('/' . $preferredUsername, $request->file('eventPostImage'),  'eventPost.png');
        }

        return $generatorValues;
    }

    public function show(Request $request): JsonResponse
    {
        $groupId = $request->input('mobilizon_group_id');
        $preferredUsername = $request->input('mobilizon_preferredusername');


        if (!$groupId && !$preferredUsername) {
            return response()->json(['error' => __('messages.materialGenerator.groupRequired')], 400);
        }

        $generatorValues = $groupId
            ? MaterialGeneratorValue::where('mobilizon_group_id', $groupId)->first()
            : MaterialGeneratorValue::where('mobilizon_preferredusername', $preferredUsername)->first();

        if (!$generatorValues && !$groupId) {
            return response()->json(['data' => null]);
        }

        $groupId = $groupId ?? $generatorValues->mobilizon_group_id;

        $mobilizon = Mobilizon::getInstance();
        $groups = $mobilizon->getGroupsAsArray();

        if (!in_array($groupId, array_column($groups, 'id'))) {
            return response()->json(['error' => __('messages.general.notInGroup')], 403);
        }

        return response()->json($generatorValues);
    }

    public function getImage(Request $request)
    {
        return Storage::disk('public')->get($request->input('path'));
    }
}
