<?php

namespace App\Enums;

enum RecordingStatus: string
{
    case Pending = 'pending';
    case Uploading = 'uploading';
    case Ready = 'ready';
    case Failed = 'failed';
}
