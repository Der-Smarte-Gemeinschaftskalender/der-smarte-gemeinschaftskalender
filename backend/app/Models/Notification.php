<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/**
 * Notification Model
 *
 * @property int $id
 * @property string $email
 * @property string|null $intervall
 * @property string $category
 * @property int|null $radius
 * @property int|null $postal_code
 * @property string $token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 */
class Notification extends Model
{

    protected $fillable = [
        'email',
        'organisation_id',
        'intervall',
        'category',
        'radius',
        'location_hash',
        'token'
    ];
}
