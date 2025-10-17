<?php

namespace App\Enums;

enum MobilizonEventStatus: string
{
    case TENTATIVE = 'TENTATIVE'; // "The event is tentative"
    case CONFIRMED = 'CONFIRMED'; // "The event is confirmed"
    case CANCELLED = 'CANCELLED'; // "The event is cancelled"

}
