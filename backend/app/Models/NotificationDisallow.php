<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/**
 * Notification Model
 *
 * @property int $id
 * @property string $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class NotificationDisallow extends Model
{
    protected $table = 'notifications_disallow';

    protected $fillable = [
        'email',
    ];
}
