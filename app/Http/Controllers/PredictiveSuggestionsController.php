<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GeneratePredictiveSuggestionsAction;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;

/**
 * Generate and retrieve predictive next-best-action suggestions for a contact.
 */
final class PredictiveSuggestionsController extends Controller
{
    public function __construct(private GeneratePredictiveSuggestionsAction $generateAction)
    {
        //
    }

    public function show(Contact $contact): JsonResponse
    {
        $suggestions = $this->generateAction->handle($contact);

        return response()->json([
            'contact_id' => $contact->id,
            'suggestions' => $suggestions,
        ]);
    }

    public function generate(Contact $contact): JsonResponse
    {
        $suggestions = $this->generateAction->handle($contact);

        return response()->json([
            'success' => true,
            'contact_id' => $contact->id,
            'suggestions' => $suggestions,
        ]);
    }
}
