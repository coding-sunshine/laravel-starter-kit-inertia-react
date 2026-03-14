<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateAiSummaryAction;
use App\Models\AiSummary;
use App\Models\Contact;
use App\Models\Lot;
use App\Models\Project;
use App\Models\PropertyReservation;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Generate and retrieve AI summaries for CRM models.
 */
final class AiSummaryController extends Controller
{
    public function __construct(private GenerateAiSummaryAction $generateAction)
    {
        //
    }

    public function generate(string $type, int $id): JsonResponse
    {
        $model = $this->resolveModel($type, $id);

        if (! $model) {
            return response()->json(['error' => 'Model not found'], Response::HTTP_NOT_FOUND);
        }

        $context = "Generate a summary for this {$type} record: ".json_encode($model->toArray());
        $summary = $this->generateAction->handle($model, $context);

        return response()->json([
            'success' => true,
            'summary' => [
                'id' => $summary->id,
                'content' => $summary->content,
                'model' => $summary->model,
                'created_at' => $summary->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function show(string $type, int $id): JsonResponse
    {
        $summary = AiSummary::query()
            ->where('summarizable_type', $this->morphClass($type))
            ->where('summarizable_id', $id)
            ->latest('created_at')
            ->first();

        if (! $summary) {
            return response()->json(['summary' => null]);
        }

        return response()->json([
            'summary' => [
                'id' => $summary->id,
                'content' => $summary->content,
                'model' => $summary->model,
                'created_at' => $summary->created_at?->toIso8601String(),
            ],
        ]);
    }

    private function resolveModel(string $type, int $id): ?Model
    {
        return match ($type) {
            'contact' => Contact::query()->find($id),
            'sale' => Sale::query()->find($id),
            'reservation' => PropertyReservation::query()->find($id),
            'lot' => Lot::query()->find($id),
            'project' => Project::query()->find($id),
            default => null,
        };
    }

    private function morphClass(string $type): string
    {
        return match ($type) {
            'contact' => Contact::class,
            'sale' => Sale::class,
            'reservation' => PropertyReservation::class,
            'lot' => Lot::class,
            'project' => Project::class,
            default => $type,
        };
    }
}
