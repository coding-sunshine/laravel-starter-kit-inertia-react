<?php

declare(strict_types=1);

namespace BeyondCode\Vouchers\Events;

use BeyondCode\Vouchers\Models\Voucher;
use Illuminate\Queue\SerializesModels;

final class VoucherRedeemed
{
    use SerializesModels;

    public $user;

    /** @var Voucher */
    public $voucher;

    public function __construct($user, Voucher $voucher)
    {
        $this->user = $user;
        $this->voucher = $voucher;
    }
}
