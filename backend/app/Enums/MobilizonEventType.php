<?php

namespace App\Enums;

enum MobilizonEventType: string
{
    case IN_PERSON = 'IN_PERSON'; // "The event will happen in person. It can also be livestreamed, but has a physical address"
    case ONLINE = 'ONLINE'; // "The event will only happen online. It has no physical address"
}
