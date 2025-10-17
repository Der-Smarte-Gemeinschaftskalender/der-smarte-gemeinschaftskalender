<?php

namespace App\Enums;

enum MobilizonEventJoinOptions: string
{
  case FREE = 'FREE'; // "Anyone can join and is automatically accepted"
  case RESTRICTED = 'RESTRICTED'; //   "Manual acceptation"
  case INVITE = 'INVITE'; //   "Participants must be invited"
  case EXTERNAL = 'EXTERNAL'; //   "External registration"
}
