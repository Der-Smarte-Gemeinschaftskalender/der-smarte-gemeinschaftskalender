<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string $mobilizon_email
 * @property mixed $mobilizon_password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonLogins newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonLogins newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonLogins query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonLogins whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonLogins whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonLogins whereMobilizonEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonLogins whereMobilizonPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonLogins whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonLogins whereUserId($value)
 * @mixin \Eloquent
 */
class MobilizonLogins extends Model
{
    //
    protected $fillable = [
        'user_id',
        'mobilizon_email',
        'mobilizon_password',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mobilizon_password' => 'encrypted',
        ];
    }
}
