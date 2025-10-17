<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Log;
use Throwable;

/**
 * 
 *
 * @property int $id
 * @property string $slug
 * @property string|null $title
 * @property int|null $mobilizon_group_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonTag whereMobilizonGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonTag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonTag whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilizonTag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MobilizonTag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'mobilizon_group_id',
    ];

    static public function saveTags(array $tags, int $groupId): void
    {
        try {
            foreach ($tags as $tag) {
                $mobilizonTag = new MobilizonTag();
                $mobilizonTag->mobilizon_group_id = $groupId;
                $mobilizonTag->title = $tag;
                $mobilizonTag->save();
            }
        } catch (Throwable $error) {
            Log::error("Error saving mobilizon tags: " . $error);
        }
    }
}
