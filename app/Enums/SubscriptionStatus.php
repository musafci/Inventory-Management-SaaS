<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Expired = 'expired';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
}
