<?php

namespace App\Enums;

enum EvaluationType: string
{
    case MIDTERM = 'midterm';
    case FINAL = 'final';
    case PERIODIC = 'periodic';
}
