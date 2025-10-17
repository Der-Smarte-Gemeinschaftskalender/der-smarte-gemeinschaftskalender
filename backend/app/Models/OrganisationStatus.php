<?php

namespace App\Models;

use App\Casts\Json;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganisationStatus extends Model
{
    protected $table = 'organisation_status';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_organisation_data' => Json::class,
        ];
    }

    public function requested_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id', 'id');
    }
}
