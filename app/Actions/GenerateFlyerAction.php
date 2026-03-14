<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Flyer;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class GenerateFlyerAction
{
    /**
     * Generate a PDF for the given flyer and return the PdfBuilder.
     *
     * Usage:
     *   $pdf = app(GenerateFlyerAction::class)->handle($flyer);
     *   return $pdf->download("flyer-{$flyer->id}.pdf");
     *   // or: $pdf->save(storage_path("app/flyers/{$flyer->id}.pdf"));
     *   // or: return $pdf->inline();
     */
    public function handle(Flyer $flyer): PdfBuilder
    {
        $flyer->loadMissing(['project', 'flyerTemplate', 'lot']);

        $project = $flyer->project;
        $template = $flyer->flyerTemplate;
        $lot = $flyer->lot;

        $developerName = null;
        if ($project?->relationLoaded('developer')) {
            $developerName = $project->developer?->name;
        } elseif ($project?->developer_id) {
            $developerName = $project->load('developer')->developer?->name;
        }

        $minPriceFormatted = $project?->min_price
            ? '$'.number_format((float) $project->min_price, 0)
            : null;

        $maxPriceFormatted = $project?->max_price
            ? '$'.number_format((float) $project->max_price, 0)
            : null;

        return Pdf::view('flyers.pdf', [
            'flyer' => $flyer,
            'project' => $project,
            'template' => $template,
            'lot' => $lot,
            'developer_name' => $developerName,
            'min_price_formatted' => $minPriceFormatted ?? 'POA',
            'max_price_formatted' => $maxPriceFormatted,
            'Str' => new Str,
        ])
            ->format('A4')
            ->margins(0, 0, 0, 0);
    }

    /**
     * Return a suggested filename for the flyer PDF.
     */
    public function filename(Flyer $flyer): string
    {
        $projectSlug = Str::slug($flyer->project?->title ?? 'project');
        $suffix = $flyer->lot_id ? '-lot-'.$flyer->lot_id : '';

        return "flyer-{$projectSlug}{$suffix}.pdf";
    }
}
