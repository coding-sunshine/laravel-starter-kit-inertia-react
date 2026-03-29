<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Indent;
use App\Models\Rake;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class DeleteIndentAction
{
    public function canDeleteWithRakeEligibility(Indent $indent): bool
    {
        $rake = Rake::query()->where('indent_id', $indent->id)->first();
        if ($rake === null) {
            return true;
        }

        return ! $rake->rakeWeighments()->exists() && ! $rake->wagonLoadings()->exists();
    }

    public function handle(Indent $indent): void
    {
        $rake = Rake::query()->where('indent_id', $indent->id)->first();

        if ($rake !== null) {
            if ($rake->rakeWeighments()->exists() || $rake->wagonLoadings()->exists()) {
                throw new InvalidArgumentException(
                    'Cannot delete this indent because the linked rake has weighments or wagon loading data.'
                );
            }

            DB::transaction(function () use ($indent, $rake): void {
                $rake->forceDelete();
                $indent->clearMediaCollection('indent_confirmation_pdf');
                $indent->clearMediaCollection('indent_pdf');
                $indent->delete();
            });

            return;
        }

        DB::transaction(function () use ($indent): void {
            $indent->clearMediaCollection('indent_confirmation_pdf');
            $indent->clearMediaCollection('indent_pdf');
            $indent->delete();
        });
    }
}
