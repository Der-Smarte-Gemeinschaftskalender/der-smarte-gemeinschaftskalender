<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\Json;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * SingleEvent Model
 *
 * @property int $id
 * @property int|null $mobilizon_group_id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $start
 * @property int|null $duration
 * @property string|null $time
 * @property array|null $mobilizon_fields
 * @property int|null $user_id
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\CreatedEvent|null $created_event
 */
class SingleEvent extends Model
{

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected function casts(): array
    {
        return [
            'mobilizon_fields' => Json::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function created_event(): HasOne
    {
        return $this->hasOne(CreatedEvent::class, 'single_events_id', 'id');
    }
}
