<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Indent;
use App\Models\Rake;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class CreateIndentAndProvisionRakeAction
{
    public function handle(Request $request, User $user, array $validated): Rake
    {
        return DB::transaction(function () use ($request, $user, $validated): Rake {
            $indent = new Indent;
            $indent->siding_id = $validated['siding_id'];
            $indent->indent_number = $validated['indent_number'] ?? null;
            $indent->state = $validated['state'] ?? 'pending';
            $indent->remarks = $validated['remarks'] ?? null;
            $indent->e_demand_reference_id = $validated['e_demand_reference_id'] ?? null;
            $indent->fnr_number = $validated['fnr_number'] ?? null;
            $indent->railway_reference_no = $validated['railway_reference_no'] ?? null;
            $indent->destination = $validated['destination'] ?? null;
            $indent->expected_loading_date = $validated['expected_loading_date'] ?? null;
            $indent->required_by_date = $validated['required_by_date'] ?? null;
            $indent->demanded_stock = $validated['demanded_stock'] ?? null;
            $indent->total_units = $validated['total_units'] ?? null;
            $indent->target_quantity_mt = $validated['target_quantity_mt'] ?? null;
            $indent->allocated_quantity_mt = $validated['allocated_quantity_mt'] ?? null;
            $indent->available_stock_mt = $validated['available_stock_mt'] ?? null;
            $this->applyIndentAt($indent, $validated['indent_at'] ?? null);
            $indent->created_by = $user->id;
            $indent->updated_by = $user->id;
            $indent->save();

            if ($request->hasFile('pdf')) {
                $indent->addMediaFromRequest('pdf')->toMediaCollection('indent_confirmation_pdf');
            }

            $rakeNumber = isset($validated['rake_number']) && mb_trim((string) $validated['rake_number']) !== ''
                ? mb_trim((string) $validated['rake_number'])
                : null;
            $rakeSerialNumber = isset($validated['rake_serial_number']) && mb_trim((string) $validated['rake_serial_number']) !== ''
                ? mb_trim((string) $validated['rake_serial_number'])
                : null;
            $priorityNumber = array_key_exists('rake_priority_number', $validated)
                ? (($validated['rake_priority_number'] ?? null) !== null ? (int) $validated['rake_priority_number'] : null)
                : null;

            return app(ProvisionRakeForIndent::class)->handle(
                $indent,
                $rakeNumber,
                $user->id,
                $priorityNumber,
                $rakeSerialNumber,
            );
        });
    }

    private function applyIndentAt(Indent $indent, mixed $value): void
    {
        if ($value === null || $value === '') {
            $indent->indent_date = null;
            $indent->indent_time = null;

            return;
        }

        $parsed = Date::parse($value);
        $indent->indent_date = $parsed;
        $indent->indent_time = $parsed;
    }
}
