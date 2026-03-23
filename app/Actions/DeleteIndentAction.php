<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Indent;
use App\Models\Rake;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class DeleteIndentAction
{
    public function handle(Indent $indent): void
    {
        if (Rake::query()->where('indent_id', $indent->id)->exists()) {
            throw new InvalidArgumentException('Cannot delete an indent that already has a rake.');
        }

        DB::transaction(function () use ($indent): void {
            $indent->clearMediaCollection('indent_confirmation_pdf');
            $indent->clearMediaCollection('indent_pdf');
            $indent->delete();
        });
    }
}
