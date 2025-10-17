<?php

namespace App\Console\Commands;

use App;
use App\Mail\SendEmailNotifcation;
use Illuminate\Console\Command;
use App\Models\Notification;
use Carbon\Carbon;
use App\Models\Mobilizon;
use Mail;
use rdx\graphqlquery\Query;


class SendEmailNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-email-nofifications  {--intervall=WEEKLY}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email notifications for events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $intervall = $this->option('intervall');
        $notifications = Notification::where('intervall', $intervall)
            ->where('is_verified', true)
            ->get();
        foreach ($notifications as $notification) {
            try {
                if ($intervall === 'WEEKLY') {
                    $events = [
                        'thisWeek' => $this->filteredWeek('thisWeek', $notification->category, $notification->organisation_id, $notification->location_hash, $notification->radius),
                        'nextWeek' => $this->filteredWeek('nextWeek', $notification->category, $notification->organisation_id, $notification->location_hash, $notification->radius),
                        'followingTwoWeeks' => $this->filteredWeek('followingTwoWeeks', $notification->category, $notification->organisation_id, $notification->location_hash, $notification->radius),
                    ];
                } else if ($intervall === 'MONTHLY') {
                    $events = [
                        'thisMonth' => $this->filteredWeek('thisMonth', $notification->category, $notification->organisation_id, $notification->location_hash, $notification->radius),
                        'nextMonth' => $this->filteredWeek('nextMonth', $notification->category, $notification->organisation_id, $notification->location_hash, $notification->radius),
                    ];
                } else {
                    $this->error("Invalid intervall: {$intervall}");
                    continue;
                }
                Mail::to($notification->email)
                    ->send(new SendEmailNotifcation($notification->id, $notification->token, $events, $intervall));
                $this->info("Email sent to {$notification->email} for notification ID {$notification->id}");
            } catch (\Exception $e) {
                $this->error("Error processing notification ID {$notification->id}: " . $e->getMessage());
                continue;
            }
        }
    }

    public function filteredWeek($filterTime, $category = null, $organisationIds = null, $locationHash = null, $radius = null)
    {
        switch ($filterTime) {
            case 'thisWeek':
                $beginsOn = Carbon::now()->startOfWeek();
                $endsOn = Carbon::now()->endOfWeek();
                break;
            case 'nextWeek':
                $beginsOn = Carbon::now()->addWeek()->startOfWeek();
                $endsOn = Carbon::now()->addWeek()->endOfWeek();
                break;
            case 'followingTwoWeeks':
                $beginsOn = Carbon::now()->addWeeks(2)->startOfWeek();
                $endsOn = Carbon::now()->addWeeks(4)->endOfWeek();
                break;
            case 'thisMonth':
                $beginsOn = Carbon::now()->startOfMonth();
                $endsOn = Carbon::now()->endOfMonth();
                break;
            case 'nextMonth':
                $beginsOn = Carbon::now()->addMonth()->startOfMonth();
                $endsOn = Carbon::now()->addMonth()->endOfMonth();
                break;
            default:
                throw new \InvalidArgumentException("Invalid filter time: {$filterTime}");
        }
        $mobilizonClient = Mobilizon::getInstanceAdmin();
        $searchCategory = $category === 'ALL' ? null : $category;
        $searchTarget = !$locationHash || !$radius ? Query::enum('INTERNAL') : Query::enum('GLOBAL');

        $result = $mobilizonClient->searchEvents([
            'beginsOn' => $beginsOn,
            'endsOn' => $endsOn,
            'category' => $searchCategory,
            'searchTarget' => $searchTarget,
            'location' => $locationHash,
            'radius' => $radius,
        ]);

        $events = $result['data']['searchEvents']['elements'];

        $events = array_map(function ($event) {
            $event['formattedBeginsOn'] = $this->formatBeginsOn($event['beginsOn']);
            return $event;
        }, $events);
        if ($organisationIds && $organisationIds !== 'ALL') {
            $events = array_filter($events, function ($event) use ($organisationIds) {
                return $event['attributedTo']['id'] === $organisationIds;
                return in_array($event['attributedTo']['id'], $organisationIds);
            });
        }
        $alternativeEvents = [];
        if (count($events) === 0) {
            $result = $mobilizonClient->searchEvents([
                'beginsOn' => $beginsOn,
                'endsOn' => $endsOn,
                'searchTarget' => $searchTarget
            ], 5);
            $alternativeEvents = $result['data']['searchEvents']['elements'];
            $alternativeEvents = array_map(function ($event) {
                $event['formattedBeginsOn'] = $this->formatBeginsOn($event['beginsOn']);
                return $event;
            }, $alternativeEvents);
        }
        return ['events' => $events, 'alternativeEvents' => $alternativeEvents];
    }


    public function formatBeginsOn($beginsOn)
    {
        Carbon::setLocale(App::getLocale());
        return Carbon::parse($beginsOn)->translatedFormat('D d.m.Y H:i') . ' Uhr';
    }
}
