<?php

namespace App\Http\Controllers;

use App\Models\Mobilizon;
use App\Models\MobilizonTag;
use App\Models\SeriesEvent;
use App\Models\CreatedEvent;
use App\Services\HolidaysService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Enums\Intervall;
use Illuminate\Support\Facades\Log;

class SeriesEventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('in_group', except: ['fetchStateHolidays'])
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $page = max($page, 1);
        $pageSize = max($pageSize, 1);

        $query = SeriesEvent::where('mobilizon_group_id', (int)$request->input('mobilizon_group_id'))
            ->orderBy('created_at', 'desc');

        $data = $query->paginate(
            $pageSize,
            ['id', 'name', 'start', 'end', 'time', 'intervall', 'duration'],
            'page',
            $page
        );

        return response()->json([
            'data' => $data->items(),
            'total' => $data->total(),
            'page' => $page,
            'pageSize' => $pageSize
        ]);
    }

    public function show(SeriesEvent $seriesEvent): JsonResponse
    {
        $seriesEvent->created_events = CreatedEvent::where('series_events_id', $seriesEvent->id)
            ->orderBy('start', 'ASC')
            ->get();
        $seriesEvent->load('user');

        return response()->json([
            'seriesEvent' => $seriesEvent
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $request->merge([
            'monthly_use_start_date_as_default' => $request->boolean('monthly_use_start_date_as_default'),
        ]);

        $validated = $request->validate([
            'holidays_state' => 'string|in:all_states,none,sh,hh,nw,he,rp,by,sl,bw,th,st,sn,be,bb,mv,ni,augsburg',
            'weekly_day' => 'nullable|integer|between:0,6',
            'monthly_week_day' => 'nullable|integer|between:0,6',
            'monthly_weeks' => 'nullable|array',
            'monthly_weeks.*' => 'integer|in:-1,1,2,3,4',
            'monthly_use_start_date_as_default' => 'nullable|boolean',
        ]);

        if (Intervall::validate($request->get('intervall')) === false) {
            return response()->json([
                'error' => 'Ungültiges Intervall.'
            ], 400);
        }

        $intervall = Intervall::toIso($request->get('intervall'));
        $weeklyDay = $request->input('weekly_day') ?? Carbon::parse($request->get('start'))->dayOfWeek;
        $monthlyWeekDay = $request->input('monthly_week_day');
        $monthlyUseStartDateAsDefault = $request->boolean('monthly_use_start_date_as_default');
        $monthlyWeeksInput = $request->input('monthly_weeks', []);
        $monthlyWeeksInput = is_array($monthlyWeeksInput)
            ? $monthlyWeeksInput
            : [$monthlyWeeksInput];

        $monthlyWeeks = array_values(array_unique(array_map('intval', $monthlyWeeksInput)));
        $mclient = Mobilizon::getInstance();

        $start = Carbon::parse($request->get('start') . ' ' . $request->get('time'));
        $end = Carbon::parse($request->get('end'));
        $period = CarbonPeriod::create($start, '1 day', $end->copy()->endOfDay());

        $durationSeperated = explode(':', $request->input('duration'));
        $durationMinutes = (int)$durationSeperated[0] * 60 + (int)$durationSeperated[1];

        $mobilizonFields = $request->input('mobilizon_fields');
        $mobilizonFields['description'] = $mobilizonFields['description'] ?: '';

        // Collect matching event dates first
        $eventDates = [];
        foreach ($period as $eventDate) {
            // Default and new behavior are similar
            if ($intervall === Intervall::WEEKLY->getLabel() && $weeklyDay != $eventDate->dayOfWeek) continue;
            
            if ($intervall === Intervall::MONTHLY->getLabel()) {
                // Default behavior
                if ($monthlyUseStartDateAsDefault) {
                    if ($start->day != $eventDate->day) continue;
                }
                else {
                    if ($eventDate->dayOfWeek != $monthlyWeekDay) continue;

                    $isMatchingWeek = false;

                    // Last week handling
                    if (in_array(-1, $monthlyWeeks, true)) {
                        if ($eventDate->copy()->endOfWeek()->addDay()->month !== $eventDate->month) {
                            $isMatchingWeek = true;
                        }
                    }

                    if (!$isMatchingWeek) {
                        foreach ($monthlyWeeks as $monthWeek) {
                            if ($monthWeek === -1) continue;

                            $nthDate = Carbon::create($eventDate->year, $eventDate->month, 1)
                                ->nthOfMonth($monthWeek, $monthlyWeekDay);

                            if ($nthDate && $nthDate->isSameDay($eventDate)) {
                                $isMatchingWeek = true;
                                break;
                            }
                        }
                    }
                    if (!$isMatchingWeek) continue;
                }
            }

            $eventDates[] = $eventDate->format('Y-m-d');
        }

        if (empty($eventDates)) {
            return response()->json([
                'error' => 'Es wurde kein Termin erstellt. Bitte überprüfe deine Angaben zum Intervall.'
            ], 400);
        }

        $checkHolidays = $request->boolean('holidays_check');
        $checkSchoolHolidays = $request->boolean('school_holidays_check');
        $holidaysState = $validated['holidays_state'];

        $skippedDates = [];

        if ($checkHolidays || $checkSchoolHolidays) {
            $skippedDates = HolidaysService::filterHolidayDates(
                $eventDates,
                $holidaysState,
                $checkHolidays,
                $checkSchoolHolidays
            );

            $eventDates = array_diff($eventDates, $skippedDates);

            if (empty($eventDates)) {
                return response()->json([
                    'error' => 'Es wurden keine Termine erstellt. Alle Termine fallen auf Feiertage oder in Schulferien.',
                ], 422);
            }
        }

        // create series event
        $seriesEvent = new SeriesEvent();
        $seriesEvent->user_id = auth()->user()->id;
        $seriesEvent->mobilizon_fields = $mobilizonFields;
        $seriesEvent->intervall = Intervall::fromIso($intervall);
        $seriesEvent->mobilizon_group_id = $request->get('mobilizon_group_id');
        $seriesEvent->name = $request->get('name');
        $seriesEvent->start = $request->get('start');
        $seriesEvent->end = $request->get('end');
        $seriesEvent->time = $request->get('time');
        $seriesEvent->duration = $request->get('duration');
        $seriesEvent->weekly_day = $weeklyDay;
        $seriesEvent->monthly_week_day = $monthlyWeekDay;
        $seriesEvent->monthly_weeks = $monthlyWeeks;
        $seriesEvent->monthly_use_start_date_as_default = $monthlyUseStartDateAsDefault;
        $seriesEvent->holidays_check = $checkHolidays;
        $seriesEvent->school_holidays_check = $checkSchoolHolidays;
        $seriesEvent->holidays_state = $holidaysState;
        $seriesEvent->save();

        $pictureResponse = null;

        foreach ($eventDates as $dateString) {
            $eventDate = Carbon::parse($dateString . ' ' . $request->get('time'));

            $eventData = CreatedEventController::buildMobilizonEventData($request, $seriesEvent, $mclient, [
                "beginsOn"  => $eventDate->toAtomString(),
                "endsOn"    => $eventDate->copy()->addMinutes($durationMinutes)->toAtomString()
            ]);

            $createdEvent = new CreatedEvent();
            $createdEvent->series_events_id = $seriesEvent->id;
            $createdEvent->user_id = $request->user()->id;
            $createdEvent->start = $dateString;
            $createdEvent->time = $eventDate->format('H:i');
            $createdEvent->duration = $request->get('duration');
            $createdEvent->save();

            if (array_key_exists('tags', $mobilizonFields) && is_array($mobilizonFields['tags'])) {
                MobilizonTag::saveTags($mobilizonFields['tags'], (int)$request->input('mobilizon_group_id'));
            }

            $mresponse = $mclient->createEvent($eventData, $request->hasFile('mobilizon_fields.picture.media.file'));

            if ($mclient->hasError($mresponse)) {
                $createdEvent->delete();

                return response()->json([
                    'error' => $mclient->getError($mresponse)
                ], 400);
            } else {
                $createdEvent->mobilizon_uuid = $mresponse['data']['createEvent']['uuid'];
                $createdEvent->mobilizon_id = $mresponse['data']['createEvent']['id'];

                if (!$pictureResponse && isset($mresponse['data']['createEvent']['picture'])) {
                    $pictureResponse = $mresponse['data']['createEvent']['picture'];
                }

                $createdEvent->save();
            }
        }

        $mobilizonFields['picture'] = $pictureResponse ?? null;
        $seriesEvent->mobilizon_fields = $mobilizonFields;
        $seriesEvent->save();

        $response = [
            'seriesEvent' => $seriesEvent->load('created_events')
        ];

        return response()->json($response);
    }

    public function delete(SeriesEvent $seriesEvent): JsonResponse
    {
        $mclient = Mobilizon::getInstance();
        foreach ($seriesEvent->created_events as $createdEvent) {
            $mresponse = $mclient->deleteEvent($createdEvent->mobilizon_id);

            // If missing event, log and continue with deletion of local event to keep data consistent
            if ($mclient->hasError($mresponse) && $mclient->getError($mresponse) !== 'Veranstaltung nicht gefunden') {
                Log::error("Error deleting event with Mobilizon ID {$createdEvent->mobilizon_id}: " . $mclient->getError($mresponse));
                return response()->json([
                    'error' => "Fehler beim Löschen der Veranstaltung mit Mobilizon ID {$createdEvent->mobilizon_id}: " . $mclient->getError($mresponse)
                ]);
            }

            $createdEvent->delete();
        }

        $seriesEvent->delete();

        return response()->json([
            'success' => 'Alle Veranstaltungen der Serie wurden erfolgreich gelöscht.'
        ]);
    }

    public function fetchStateHolidays(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state' => 'string|in:all_states,none,sh,hh,nw,he,rp,by,sl,bw,th,st,sn,be,bb,mv,ni,augsburg',
        ]);

        $state = $validated['state'];
        $year = $request->input('year', Carbon::now()->year);
        $holidayCheck = $request->boolean('holidays_check');
        $schoolHolidayCheck = $request->boolean('school_holidays_check');

        try {
            $holidays = HolidaysService::fetchStateHolidays($holidayCheck, $schoolHolidayCheck, $year, $state);
            return response()->json([
                'holidays' => $holidays
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching holidays for state {$state}: " . $e->getMessage());
            return response()->json([
                'error' => 'Fehler beim Abrufen der Feiertage. Bitte versuche es später erneut.'
            ], 500);
        }
    }
}
