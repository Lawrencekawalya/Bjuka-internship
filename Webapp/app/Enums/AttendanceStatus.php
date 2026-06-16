<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case LATE = 'late';
    case PARTIAL = 'partial';
    case ABSENT = 'absent';
}
