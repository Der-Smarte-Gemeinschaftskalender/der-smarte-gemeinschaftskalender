<?php

namespace App\Models;

use App\Casts\Json;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $url
 * @property int $is_active
 * @property int $user_id
 * @property int $mobilizon_group_id
 * @property array $mobilizon_fields
 * @property-read \App\Models\CreatedEvent|null $created_events
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent whereMobilizonFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent whereMobilizonGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportedEvent whereUserId($value)
 * @mixin \Eloquent
 */
class ImportedEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'url',
        'is_active',
        'user_id',
        'mobilizon_group_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mobilizon_fields' => Json::class,
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function created_events(): HasMany
    {
        return $this->hasMany(CreatedEvent::class, 'imported_events_id');
    }
}
