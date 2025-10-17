<?php

namespace App\Enums;

enum MobilizonOpenness: string
{
  case INVITE_ONLY = 'INVITE_ONLY'; // "The actor can only be followed by invitation"
  case MODERATED = 'MODERATED'; // "The actor needs to accept the following before it's effective"
  case OPEN = 'OPEN'; // "The actor is open to followings"
}
