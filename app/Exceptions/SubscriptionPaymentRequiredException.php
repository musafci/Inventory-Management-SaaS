<?php

namespace App\Exceptions;

use Exception;

class SubscriptionPaymentRequiredException extends Exception
{
    public function __construct(string $message = 'Payment required. Choose a plan to continue making changes.')
    {
        parent::__construct($message);
    }
}
