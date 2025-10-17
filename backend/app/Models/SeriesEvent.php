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
 * @property string $name
 * @property string|null $description
 * @property string $intervall
 * @property string $start
 * @property string $end
 * @property string $time
 * @property int $duration
 * @property int $user_id
 * @property int $mobilizon_group_id
 * @property array $mobilizon_fields
 * @property-read \App\Models\CreatedEvent|null $created_events
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereIntervall($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereMobilizonFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereMobilizonGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeriesEvent whereUserId($value)
 * @mixin \Eloquent
 */
class SeriesEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'intervall',
        'start',
        'end',
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
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function created_events(): HasMany
    {
        return $this->hasMany(CreatedEvent::class, 'series_events_id', 'id');
    }
}
