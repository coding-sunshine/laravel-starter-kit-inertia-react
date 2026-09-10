<?php

declare(strict_types=1);

namespace BeyondCode\Vouchers;

use Illuminate\Support\Str;

final class VoucherGenerator
{
    private $characters;

    private $mask;

    private $prefix;

    private $suffix;

    private $separator = '-';

    private $generatedCodes = [];

    public function __construct(string $characters = 'ABCDEFGHJKLMNOPQRSTUVWXYZ234567890', string $mask = '****-****')
    {
        $this->characters = $characters;
        $this->mask = $mask;
    }

    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function setSuffix(?string $suffix): void
    {
        $this->suffix = $suffix;
    }

    public function setSeparator(string $separator): void
    {
        $this->separator = $separator;
    }

    public function generateUnique(): string
    {
        $code = $this->generate();

        while (in_array($code, $this->generatedCodes) === true) {
            $code = $this->generate();
        }

        $this->generatedCodes[] = $code;

        return $code;
    }

    public function generate(): string
    {
        $length = mb_substr_count($this->mask, '*');

        $code = $this->getPrefix();
        $mask = $this->mask;
        $characters = collect(mb_str_split($this->characters));

        for ($i = 0; $i < $length; $i++) {
            $mask = Str::replaceFirst('*', $characters->random(1)->first(), $mask);
        }

        $code .= $mask;
        $code .= $this->getSuffix();

        return $code;
    }

    private function getPrefix(): string
    {
        return $this->prefix !== null ? $this->prefix.$this->separator : '';
    }

    private function getSuffix(): string
    {
        return $this->suffix !== null ? $this->separator.$this->suffix : '';
    }
}
