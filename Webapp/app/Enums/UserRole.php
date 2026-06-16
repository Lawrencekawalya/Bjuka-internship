<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case INTERN = 'intern';
    case HR = 'hr';
    case MANAGER = 'manager';
    case CENTER_DIRECTOR = 'center_director';
}
