<?php

namespace App\Enums;

enum RoundStatus: string
{
    case Pending   = 'pending';    // not yet started
    case Active    = 'active';     // buzzers open
    case Locked    = 'locked';     // first buzz accepted, awaiting judge decision
    case Completed = 'completed';  // judge resolved the round
}
