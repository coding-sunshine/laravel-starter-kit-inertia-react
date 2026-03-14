<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePaymentStageAction;
use App\Models\PaymentStage;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentStageController extends Controller
{
    public function index(Sale $sale): JsonResponse
    {
        $stages = $sale->paymentStages()->orderBy('order')->orderBy('created_at')->get();

        return response()->json($stages);
    }

    public function store(Request $request, Sale $sale, CreatePaymentStageAction $action): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:eoi,deposit,commission,payout'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $paymentStage = $action->handle($sale, $validated);

        return response()->json($paymentStage, 201);
    }

    public function update(Request $request, PaymentStage $paymentStage): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['sometimes', 'string', 'in:eoi,deposit,commission,payout'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $paymentStage->update($validated);

        return response()->json($paymentStage);
    }

    public function destroy(PaymentStage $paymentStage): JsonResponse
    {
        $paymentStage->delete();

        return response()->json(['success' => true]);
    }
}
