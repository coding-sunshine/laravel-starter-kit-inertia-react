<?php

declare(strict_types=1);

namespace BeyondCode\Vouchers\Tests\Models;

use BeyondCode\Vouchers\Traits\HasVouchers;
use Illuminate\Database\Eloquent\Model;

final class Item extends Model
{
    use HasVouchers;

    protected $fillable = ['name'];
}
