<?php

declare(strict_types=1);

namespace BeyondCode\Vouchers\Exceptions;

use BeyondCode\Vouchers\Models\Voucher;
use Exception;

final class VoucherExpired extends Exception
{
    protected $message = 'The voucher is already expired.';

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
