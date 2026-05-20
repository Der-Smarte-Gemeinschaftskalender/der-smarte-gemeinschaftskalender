<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Log;
use Throwable;

class HolidaysService
{
    private static string $holidaysApiUrl = 'https://get.api-feiertage.de';
    private static string $schoolHolidaysApiUrl = 'https://ferien-api.maxleistner.de/api/v2/';
    private static int $cacheDuration = 86400 * 90; // Quarter year in seconds

    private static function fetchHolidays(int $year, string $state): array
    {
        try {
            $cacheKey = "holidays_{$year}_" . strtolower($state);
            
            return Cache::remember($cacheKey, self::$cacheDuration, function () use ($year, $state) {
                $normalizedState = match (strtolower($state)) {
                    'all_states' => '',
                    'augsburg' => 'by',
                    default => strtolower($state),
                };
                
                $response = Http::timeout(10)->get(self::$holidaysApiUrl, [
                    'years' => $year,
                    'states' => $normalizedState,
                    'augsburg' => $state === 'augsburg' ? '1' : '0',
                ]);

                if (!$response->successful()) return [];
                
                $holidays = $response->json('feiertage', []);
                
                return array_values(array_filter($holidays, function ($holiday) use ($normalizedState) {
                    if ($normalizedState === '') return true;
                    
                    return isset($holiday[$normalizedState]) && $holiday[$normalizedState] === '1';
                }));
            });
        } catch (Throwable $e) {
            Log::error('Error fetching holidays: ' . $e->getMessage());
            return [];
        }
    }

    private static function fetchSchoolHolidays(int $year, string $state = 'SH'): array
    {
        try {
            $cacheKey = "school_holidays_{$year}_{$state}";
            
            return Cache::remember($cacheKey, 86400 * 365, function () use ($year, $state) {
                $state = match (strtolower($state)) {
                    'all_states' => '',
                    'augsburg' => 'by',
                    default => strtolower($state),
                };

                $response = Http::timeout(10)->get(self::$schoolHolidaysApiUrl . "{$year}?states={$state}");

                if (!$response->successful()) return [];

                $schoolHolidays = $response->json();

                $normalizedSchoolHolidays = array_values(array_map(function ($holiday) {
                    if (isset($holiday['start'])) {
                        $holiday['start'] = Carbon::parse($holiday['start'])->format('Y-m-d');
                    }

                    if (isset($holiday['end'])) {
                        $holiday['end'] = Carbon::parse($holiday['end'])->format('Y-m-d');
                    }

                    return $holiday;
                }, $schoolHolidays));

                return self::dedupeSchoolHolidays($normalizedSchoolHolidays);
            });
        } catch (Throwable $e) {
            Log::error('Error fetching school holidays: ' . $e->getMessage());
            return [];
        }
    }

    public static function isHoliday(string $date, string $state): bool
    {
        if ($state === 'none') return false;

        try {
            $parsedDate = Carbon::parse($date);
            $year = $parsedDate->year;
            $dateString = $parsedDate->format('Y-m-d');

            $holidays = self::fetchHolidays($year, $state);

            foreach ($holidays as $holiday) {
                if ($holiday['date'] === $dateString) {
                    return true;
                }
            }

            return false;
        } catch (Throwable $e) {
            Log::error('Error checking holiday: ' . $e->getMessage());
            return false;
        }
    }

    public static function isSchoolHoliday(string $date, string $state): bool
    {
        if ($state === 'none') return false;

        try {
            $parsedDate = Carbon::parse($date);
            $year = $parsedDate->year;

            $schoolHolidays = self::fetchSchoolHolidays($year, $state);

            foreach ($schoolHolidays as $holiday) {
                $startDate = Carbon::parse($holiday['start'])->startOfDay();
                $endDate = Carbon::parse($holiday['end'])->endOfDay();
                
                if ($parsedDate >= $startDate && $parsedDate <= $endDate) {
                    return true;
                }
            }

            return false;
        } catch (Throwable $e) {
            Log::error('Error checking school holiday: ' . $e->getMessage());
            return false;
        }
    }

    public static function fetchStateHolidays(bool $fetchHolidays, bool $fetchSchoolHolidays, int $year, string $state): array
    {    
        $holidayInfo = $fetchHolidays ? self::fetchHolidays($year, $state) : [];
        $schoolHolidayInfo = $fetchSchoolHolidays ? self::fetchSchoolHolidays($year, $state) : [];

        return [
            'holiday' => array_values($holidayInfo),
            'school_holiday' => array_values($schoolHolidayInfo),
        ];
    }

    public static function filterHolidayDates(
        array $dates,
        string $state,
        bool $checkHolidays = false,
        bool $checkSchoolHolidays = false
    ): array
    {
        $skippedDates = [];

        if (!$checkHolidays && !$checkSchoolHolidays) {
            return [];
        }

        foreach ($dates as $date) {
            if ($checkHolidays && self::isHoliday($date, $state)) {
                $skippedDates[] = $date;
                continue;
            }

            if ($checkSchoolHolidays && self::isSchoolHoliday($date, $state)) {
                $skippedDates[] = $date;
            }
        }

        return $skippedDates;
    }

    /**
     * If there are multiple entries for the same school holiday
     * (which can happen when fetching for "all_states"), this function deduplicates them based on start date, end date and name.
     */
    private static function dedupeSchoolHolidays(array $schoolHolidays): array
    {
        $seen = [];
        $uniqueSchoolHolidays = [];

        foreach ($schoolHolidays as $holiday) {
            $name = (string) ($holiday['name'] ?? $holiday['name_cp'] ?? '');
            $dedupeKey = ($holiday['start'] ?? '') . '_' . ($holiday['end'] ?? '') . '_' . strtolower(trim($name));

            if (isset($seen[$dedupeKey])) {
                continue;
            }

            $seen[$dedupeKey] = true;
            $uniqueSchoolHolidays[] = $holiday;
        }

        return $uniqueSchoolHolidays;
    }
}
