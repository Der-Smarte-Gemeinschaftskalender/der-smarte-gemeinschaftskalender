<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 *
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $mobilizon_uuid
 * @property int|null $mobilizon_id
 * @property int|null $mobilizon_group_id
 * @property int|null $single_events_id
 * @property int|null $series_events_id
 * @property int|null $imported_events_id
 * @property int|null $uploaded_events_id
 * @property int|null $ical_update_sequence
 * @property int|null $ical_id
 * @property string $start
 * @property string $time
 * @property string $duration
 * @property int $user_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ImportedEvent> $import_events
 * @property-read int|null $import_events_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent whereIcalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent whereImportedEventsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent whereMobilizonUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent whereSeriesEventsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent whereUploadedEventsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatedEvent whereUserId($value)
 * @mixin \Eloquent
 */
class CreatedEvent extends Model
{
    protected $fillable = [
        'mobilizon_uuid',
        'series_events_id',
        'single_events_id',
        'imported_events_id',
        'uploaded_events_id',
        'ical_id',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function series_event(): BelongsTo
    {
        return $this->belongsTo(SeriesEvent::class, 'series_events_id');
    }

    public function single_event(): BelongsTo
    {
        return $this->belongsTo(SingleEvent::class, 'single_events_id');
    }

    public function imported_event(): BelongsTo
    {
        return $this->belongsTo(ImportedEvent::class, 'imported_events_id');
    }

    public function uploaded_event(): BelongsTo
    {
        return $this->belongsTo(UploadedEvent::class, 'uploaded_events_id');
    }

    public function getRelatedEvent()
    {
        return $this->single_event()
            ->getResults()
            ?? $this->series_event()->getResults()
            ?? $this->uploaded_event()->getResults()
            ?? $this->imported_event()->getResults();
    }

    public function getStatus()
    {
        return optional($this->getRelatedEvent())->mobilizon_fields['status'] ?? null;
    }
}
