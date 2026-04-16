<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Indent;
use App\Models\Rake;
use App\Models\StockLedger;
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

        if ($rake->rakeWeighments()->exists() || $rake->wagonLoadings()->exists()) {
            return false;
        }

        return ! $this->rakeHasStockLedgerReferences($rake);
    }

    public function handle(Indent $indent): void
    {
        $rake = Rake::query()->where('indent_id', $indent->id)->first();

        if ($rake !== null) {
            if (
                $rake->rakeWeighments()->exists()
                || $rake->wagonLoadings()->exists()
                || $this->rakeHasStockLedgerReferences($rake)
            ) {
                throw new InvalidArgumentException(
                    'Cannot delete this indent because the linked rake has weighments, wagon loading data, or stock ledger entries.'
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

    private function rakeHasStockLedgerReferences(Rake $rake): bool
    {
        return StockLedger::query()->where('rake_id', $rake->id)->exists();
    }
}
