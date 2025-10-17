<?php

namespace App\Enums;

enum Intervall: string
{
    case WEEKLY = 'WEEKLY';
    case MONTHLY = 'MONTHLY';
    case YEARLY = 'YEARLY';

    public static function defaultInterval(bool $asIso = false): Intervall | string
    {
        return $asIso
            ? self::toIso(self::WEEKLY->value)
            : self::WEEKLY;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::WEEKLY => 'P1W',
            self::MONTHLY => 'P1M',
            self::YEARLY => 'P1Y',
        };
    }

    public static function validate(string $interval): bool
    {
        return in_array(
            strtoupper($interval),
            array_column(self::cases(), 'value'),
            strict: true
        );
    }

    public static function toIso(string | Intervall $interval): string
    {
        $interval = $interval instanceof self
            ? $interval->value
            : $interval;

        return match (strtoupper($interval)) {
            self::WEEKLY->value => 'P1W',
            self::MONTHLY->value => 'P1M',
            self::YEARLY->value => 'P1Y',
            default => self::defaultInterval(true)->value
        };
    }

    public function fromIso(string $interval): string
    {
        return match ($interval) {
            'P1W' => self::WEEKLY->value,
            'P1M' => self::MONTHLY->value,
            'P1Y' => self::YEARLY->value,
            default => self::defaultInterval()
        };
    }
}
