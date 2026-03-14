<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomFieldController extends Controller
{
    public function index(): Response
    {
        $organizationId = TenantContext::id();

        $fields = CustomField::query()
            ->where('organization_id', $organizationId)
            ->orderBy('entity_type')
            ->orderBy('sort_order')
            ->get();

        $grouped = $fields->groupBy('entity_type')->map(fn ($group) => $group->values());

        return Inertia::render('custom-fields/index', [
            'customFields' => $grouped,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'in:contact,sale,lot,project'],
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:100', 'alpha_dash'],
            'type' => ['required', 'string', 'in:text,number,date,select,multi_select,checkbox,url'],
            'options' => ['nullable', 'array'],
            'is_required' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        CustomField::query()->create([
            ...$validated,
            'organization_id' => TenantContext::id(),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Custom field created.');
    }

    public function update(Request $request, CustomField $customField): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:text,number,date,select,multi_select,checkbox,url'],
            'options' => ['nullable', 'array'],
            'is_required' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $customField->update($validated);

        return back()->with('success', 'Custom field updated.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $customField->delete();

        return back()->with('success', 'Custom field deleted.');
    }

    public function values(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
        ]);

        $organizationId = TenantContext::id();

        $fields = CustomField::query()
            ->where('organization_id', $organizationId)
            ->where('entity_type', $validated['entity_type'])
            ->with(['customFieldValues' => function ($query) use ($validated): void {
                $query->where('entity_type', $validated['entity_type'])
                    ->where('entity_id', $validated['entity_id']);
            }])
            ->orderBy('sort_order')
            ->get();

        $result = $fields->map(function (CustomField $field): array {
            $value = $field->customFieldValues->first();

            return [
                'id' => $field->id,
                'name' => $field->name,
                'key' => $field->key,
                'type' => $field->type,
                'options' => $field->options,
                'is_required' => $field->is_required,
                'value' => $value?->value,
            ];
        });

        return response()->json(['data' => $result]);
    }
}
