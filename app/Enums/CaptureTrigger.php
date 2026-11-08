<?php

namespace App\Enums;

enum CaptureTrigger: string
{
    case Pir = 'pir';
    case Manual = 'manual';
    case Interval = 'interval';
}
