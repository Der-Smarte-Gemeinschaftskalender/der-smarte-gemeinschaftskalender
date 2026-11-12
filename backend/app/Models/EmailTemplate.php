<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\EmailTemplateType;
use Illuminate\Support\Facades\App;

/**
 * 
 *
 * @property int $id
 * @property EmailTemplateType|null $on_event
 * @property string $locale
 * @property string $subject
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereOnEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailTemplate extends Model
{

    protected $fillable = [
        'on_event',
        'locale',
        'subject',
        'body',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'on_event' => EmailTemplateType::class,
    ];

    public function getOnEventLabel(): string
    {
        return $this->on_event?->getLabel() ?? 'DEFAULT';
    }

    /**
     * Template for the given event in the given language (default: the current app locale).
     * Falls back to the fallback locale and finally to the built-in default texts,
     * so sending an email never fails because a template row is missing.
     */
    public static function forEvent(EmailTemplateType $type, ?string $locale = null): self
    {
        $locale ??= App::getLocale();
        $fallbackLocale = config('app.fallback_locale');

        $templates = self::where('on_event', $type)
            ->whereIn('locale', array_unique([$locale, $fallbackLocale]))
            ->get()
            ->keyBy('locale');

        return $templates->get($locale)
            ?? $templates->get($fallbackLocale)
            ?? new self([
                'on_event' => $type,
                'locale' => $locale,
                'subject' => $type->getSubject($locale),
                'body' => $type->getBody($locale),
            ]);
    }
}
