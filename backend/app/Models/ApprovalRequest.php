<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string|null $requestable_type
 * @property int|null $requestable_id
 * @property string $request_type
 * @property array $payload
 * @property string $status
 * @property int|null $created_by_user_id
 * @property int|null $handled_by_user_id
 * @property string|null $admin_comment
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requestable_type',
        'requestable_id',
        'request_type',
        'payload',
        'status',
        'created_by_user_id',
        'handled_by_user_id',
        'admin_comment',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Polymorphic relation to the requested resource (Organization, Event, etc.)
     */
    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * User who created the approval request
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
    
    /**
     * User(Admin) who handled the approval request
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}