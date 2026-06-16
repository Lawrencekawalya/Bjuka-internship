<?php

namespace App\Enums;

enum BatchStatus: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';
}
