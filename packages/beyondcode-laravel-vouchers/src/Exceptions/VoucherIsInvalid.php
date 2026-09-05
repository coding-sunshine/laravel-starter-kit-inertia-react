<?php

declare(strict_types=1);

namespace BeyondCode\Vouchers\Exceptions;

use Exception;

final class VoucherIsInvalid extends Exception
{
    protected $code;

    public function __construct($message, $code)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public static function withCode(string $code)
    {
        return new self('The provided code '.$code.' is invalid.', $code);
    }

    /**
     * @return mixed
     */
    public function getVoucherCode()
    {
        return $this->code;
    }
}
