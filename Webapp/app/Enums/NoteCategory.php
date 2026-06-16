<?php

namespace App\Enums;

enum NoteCategory: string
{
    case TECHNICAL = 'technical';
    case COMMUNICATION = 'communication';
    case TEAMWORK = 'teamwork';
    case PROBLEM_SOLVING = 'problem_solving';
    case DISCIPLINE = 'discipline';
    case GENERAL = 'general';
}
