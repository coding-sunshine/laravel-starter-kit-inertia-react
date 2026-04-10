<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class VehicleWorkorder extends Model
{
    protected $table = 'vehicle_workorders';

    protected $guarded = [];

    public function siding()
    {
        return $this->belongsTo(Siding::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_order_date' => 'date',
            'issued_date' => 'date',
            'regd_date' => 'date',
            'permit_validity_date' => 'date',
            'tax_validity_date' => 'date',
            'fitness_validity_date' => 'date',
            'insurance_validity_date' => 'date',
        ];
    }
}
