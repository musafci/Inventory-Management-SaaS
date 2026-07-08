<?php

namespace App\Enums;

enum OrganizationStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
}
