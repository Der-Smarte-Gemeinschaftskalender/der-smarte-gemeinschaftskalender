<?php

namespace App\Support;

use Carbon\Carbon;

final class EventDateTime
{
    /**
     * Point in time of a date ("Y-m-d") and a wall clock time ("H:i") as entered in the
     * event form, interpreted in the event's timezone.
     */
    public static function fromDateAndTime(?string $date, ?string $time, string $timezone): Carbon
    {
        return Carbon::parse($date . ' ' . $time, $timezone);
    }

    /**
     * Start of a Mobilizon event in its own timezone (options.timezone), e.g. to store
     * the local date and time in created_events.
     */
    public static function startInEventTimezone(array $event): Carbon
    {
        return Carbon::parse($event['beginsOn'])->setTimezone($event['options']['timezone']);
    }
}
