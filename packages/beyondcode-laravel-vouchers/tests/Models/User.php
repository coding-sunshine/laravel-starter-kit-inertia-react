<?php

declare(strict_types=1);

namespace BeyondCode\Vouchers\Tests\Models;

use BeyondCode\Vouchers\Traits\CanRedeemVouchers;

final class User extends \Illuminate\Foundation\Auth\User
{
    use CanRedeemVouchers;
}
