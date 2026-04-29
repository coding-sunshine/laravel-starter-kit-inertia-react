<?php

declare(strict_types=1);

namespace BeyondCode\Vouchers\Exceptions;

use BeyondCode\Vouchers\Models\Voucher;
use Exception;

final class VoucherAlreadyRedeemed extends Exception
{
    protected $message = 'The voucher was already redeemed.';

    protected $voucher;

    public function __construct(Voucher $voucher)
    {
        $this->voucher = $voucher;
    }

    public static function create(Voucher $voucher)
    {
        return new self($voucher);
    }
}
