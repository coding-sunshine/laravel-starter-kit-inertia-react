<?php

declare(strict_types=1);

namespace App\Enums\Billing;

enum PaymentGatewayType: string
{
    case Stripe = 'stripe';
    case Paddle = 'paddle';
    case LemonSqueezy = 'lemon_squeezy';
    case Manual = 'manual';
}
