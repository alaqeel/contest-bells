<?php

namespace App\Enums;

enum CompetitionStatus: string
{
    case Setup  = 'setup';
    case Active = 'active';
    case Ended  = 'ended';
}
