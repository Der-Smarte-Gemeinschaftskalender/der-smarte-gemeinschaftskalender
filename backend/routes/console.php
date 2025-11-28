<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('app:fetch-imported-events')->withoutOverlapping()->hourly(); //->cron('0 0 * * *');

Schedule::command('app:send-email-nofifications  --intervall=WEEKLY')->withoutOverlapping()->weeklyOn(1, '6:00'); // Jeden Montag um 6 Uhr
Schedule::command('app:send-email-nofifications  --intervall=MONTHLY')->withoutOverlapping()->monthlyOn(1, '6:00');    // Jeden 1. Tag des Monats um 6 Uhr
