<?php

namespace App\Enums;

enum TaskStatus: string
{
    case All = 'all';
    case Pending = 'pending';
    case Completed = 'completed';
}
