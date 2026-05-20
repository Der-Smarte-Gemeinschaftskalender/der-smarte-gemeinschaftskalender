<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialGeneratorTemplate extends Model
{
    protected $fillable = [
        'mobilizon_group_id',
        'name',
        'material_type',
        'dimension',
        'global_settings',
        'objects_data',
    ];

    protected $casts = [
        'global_settings' => 'array',
        'objects_data' => 'array',
    ];
}
