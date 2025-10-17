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
 * @property int $user_id
 * @property int $mobilizon_group_id
 * @property array $mobilizon_fields
 * @property string $filename
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CreatedEvent> $created_events
 * @property-read int|null $created_events_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent whereMobilizonFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent whereMobilizonGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadedEvent whereUserId($value)
 * @mixin \Eloquent
 */
class UploadedEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'filename',
        'user_id',
        'mobilizon_group_id',
        'mobilizon_fields'
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
        return $this->hasMany(CreatedEvent::class, 'uploaded_events_id');
    }
}
