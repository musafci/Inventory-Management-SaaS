<?php

namespace App\Enums;

enum IdempotencyKeyStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
}
