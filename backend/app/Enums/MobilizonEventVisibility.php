<?php

namespace App\Enums;

enum MobilizonEventVisibility: string
{
    case PUBLIC = 'PUBLIC'; // "Publicly listed and federated. Can be shared."
    case UNLISTED = 'UNLISTED'; // "Visible only to people with the link - or invited"
    case RESTRICTED = 'RESTRICTED'; // "Visible only after a moderator accepted"
    case PRIVATE = 'PRIVATE'; // "Visible only to people members of the group or followers of the person"
}
