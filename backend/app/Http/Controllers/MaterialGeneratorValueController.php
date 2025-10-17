<?php

namespace App\Http\Controllers;

use App\Models\MaterialGeneratorValue;
use App\Models\Mobilizon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Storage;

class MaterialGeneratorValueController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return ['auth'];
    }
    public function upsert(Request $request)
    {
        // TODO: check if user is part of group
        $mobilizonGroupId = $request->input('mobilizon_group_id');
        $mclient = Mobilizon::getInstance();
        $group = $mclient->getGroup($mobilizonGroupId);
        $generatorValues = MaterialGeneratorValue::where('mobilizon_group_id', $request->input('mobilizon_group_id'))->first();
        $preferredUsername = $request->input('mobilizon_preferredusername');
        if (!$generatorValues) {
            $generatorValues = new MaterialGeneratorValue();
        }
        $generatorValues->mobilizon_preferredusername = $preferredUsername;
        $generatorValues->mobilizon_group_id = $mobilizonGroupId;
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

    public function show(Request $request)
    {
        // TODO: check if user is part of group
        if ($request->input('mobilizon_group_id')) {
            return MaterialGeneratorValue::where('mobilizon_group_id', $request->input('mobilizon_group_id'))->first();
        } else if ($request->input('mobilizon_preferredusername')) {
            return MaterialGeneratorValue::where('mobilizon_preferredusername', $request->input('mobilizon_preferredusername'))->first();
        }
    }

    public function getImage(Request $request)
    {
        return Storage::disk('public')->get($request->input('path'));
    }
}
