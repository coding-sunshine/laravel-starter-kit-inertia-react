<?php

declare(strict_types=1);

namespace App\Http\Controllers\Indents;

use App\Http\Controllers\Controller;
use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use App\Services\IndentPdfImporter;
use Closure;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

final class IndentsController extends Controller
{
    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        try {
            $indent = app(IndentPdfImporter::class)->import(
                $validated['pdf'],
                $user->id,
                $sidingIds
            );

            return to_route('indents.show', $indent)
                ->with('success', 'Indent imported from PDF successfully.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['pdf' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'pdf' => 'Import failed: '.$e->getMessage(),
            ]);
        }
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $indents = Indent::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('indents/index', [
            'indents' => $indents,
            'sidings' => $sidings,
        ]);
    }

    public function create(Request $request): Response
    {
        // $this->authorize('create', Indent::class);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('indents/create', [
            'sidings' => $sidings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', Indent::class);

        $validated = $request->validate([
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'indent_number' => ['nullable', 'string', 'max:20', 'unique:indents,indent_number'],
            'state' => ['nullable', 'string', 'in:pending,allocated,partial,completed,cancelled'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'e_demand_reference_id' => ['nullable', 'string', 'max:100'],
            'fnr_number' => ['nullable', 'string', 'max:50'],
            'expected_loading_date' => ['nullable', 'date'],
            'demanded_stock' => ['nullable', 'string', 'max:255'],
            'total_units' => ['nullable', 'string', 'max:255'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $indent = new Indent;
        $indent->siding_id = $validated['siding_id'];
        $indent->indent_number = $validated['indent_number'] ?? null;
        $indent->state = $validated['state'] ?? 'pending';
        $indent->remarks = $validated['remarks'] ?? null;
        $indent->e_demand_reference_id = $validated['e_demand_reference_id'] ?? null;
        $indent->fnr_number = $validated['fnr_number'] ?? null;
        $indent->expected_loading_date = $validated['expected_loading_date'] ?? null;
        $indent->demanded_stock = $validated['demanded_stock'] ?? null;
        $indent->total_units = $validated['total_units'] ?? null;
        $indent->created_by = $request->user()->id;
        $indent->updated_by = $request->user()->id;
        $indent->save();

        if ($request->hasFile('pdf')) {
            $indent->addMediaFromRequest('pdf')->toMediaCollection('indent_confirmation_pdf');
        }

        return to_route('indents.show', $indent)
            ->with('success', 'Indent created.');
    }

    public function downloadPdf(Indent $indent): \Illuminate\Contracts\Support\Responsable
    {
        $media = $indent->getFirstMedia('indent_pdf')
            ?? $indent->getFirstMedia('indent_confirmation_pdf');
        if (! $media) {
            abort(404, 'No PDF attached to this indent.');
        }

        return $media;
    }

    public function show(Request $request, Indent $indent): Response
    {
        // $this->authorize('view', $indent);

        $indent->load([
            'siding:id,name,code',
        ]);

        // Load rake relationship safely without causing attribute errors
        $rake = Rake::where('indent_id', $indent->id)
            ->select('id', 'rake_number', 'state')
            ->first();

        $hasPdf = $indent->getFirstMedia('indent_pdf')
            || $indent->getFirstMedia('indent_confirmation_pdf');
        $indent->setAttribute('indent_pdf_download_url', $hasPdf ? route('indents.pdf', $indent) : null);

        return Inertia::render('indents/show', [
            'indent' => $indent,
            'rake' => $rake,
        ]);
    }

    public function edit(Request $request, Indent $indent): Response
    {
        // $this->authorize('update', $indent);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $indent->load('siding:id,name,code');

        return Inertia::render('indents/edit', [
            'indent' => $indent,
            'sidings' => $sidings,
        ]);
    }

    public function update(Request $request, Indent $indent): RedirectResponse
    {
        // $this->authorize('update', $indent);

        $validated = $request->validate([
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'indent_number' => ['nullable', 'string', 'max:20', 'unique:indents,indent_number,'.$indent->id],
            'state' => ['nullable', 'string', 'in:pending,allocated,partial,completed,cancelled'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'e_demand_reference_id' => ['nullable', 'string', 'max:100'],
            'fnr_number' => ['nullable', 'string', 'max:50'],
            'expected_loading_date' => ['nullable', 'date'],
            'demanded_stock' => ['nullable', 'string', 'max:255'],
            'total_units' => ['nullable', 'string', 'max:255'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $indent->siding_id = $validated['siding_id'];
        $indent->indent_number = $validated['indent_number'] ?? null;
        $indent->state = $validated['state'] ?? $indent->state;
        $indent->remarks = $validated['remarks'] ?? null;
        $indent->e_demand_reference_id = $validated['e_demand_reference_id'] ?? null;
        $indent->fnr_number = $validated['fnr_number'] ?? null;
        $indent->expected_loading_date = $validated['expected_loading_date'] ?? null;
        $indent->demanded_stock = $validated['demanded_stock'] ?? null;
        $indent->total_units = $validated['total_units'] ?? null;
        $indent->updated_by = $request->user()->id;
        $indent->save();

        if ($request->hasFile('pdf')) {
            $indent->clearMediaCollection('indent_confirmation_pdf');
            $indent->addMediaFromRequest('pdf')->toMediaCollection('indent_confirmation_pdf');
        }

        return to_route('indents.show', $indent)
            ->with('success', 'Indent updated.');
    }

    /**
     * Show the form to create a rake from this indent
     */
    public function createRake(Request $request, Indent $indent): Response
    {
        // $this->authorize('createRake', $indent);

        // Check if rake already exists for this indent
        if ($indent->rake) {
            return to_route('indents.show', $indent)
                ->with('error', 'A rake already exists for this indent.');
        }

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $maxPriorityThisMonthOnSiding = Rake::query()
            ->where('siding_id', $indent->siding_id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->max('priority_number');

        $nextPriorityNumber = (int) $maxPriorityThisMonthOnSiding + 1;

        return Inertia::render('rakes/create-from-indent', [
            'indent' => $indent->load('siding:id,name,code'),
            'sidings' => $sidings,
            'next_priority_number' => $nextPriorityNumber,
        ]);
    }

    /**
     * Store a new rake created from an indent
     */
    public function storeRakeFromIndent(Request $request, Indent $indent): RedirectResponse
    {
        // $this->authorize('createRake', $indent);

        // Check if rake already exists for this indent
        if ($indent->rake) {
            return to_route('indents.show', $indent)
                ->with('error', 'A rake already exists for this indent.');
        }
        $validated = $request->validate([
            'rake_number' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($indent) {
                    $trimmed = $value !== null && mb_trim((string) $value) !== '' ? mb_trim((string) $value) : null;
                    if ($trimmed === null) {
                        return;
                    }
                    $existsInMonth = Rake::query()
                        ->where('rake_number', $trimmed)
                        ->where('siding_id', $indent->siding_id)
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->exists();
                    if ($existsInMonth) {
                        $fail('This rake number is already in use this month.');
                    }
                },
            ],
            'rake_priority_number' => [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, Closure $fail) use ($indent) {
                    if (! is_numeric($value)) {
                        return;
                    }
                    $num = (int) $value;
                    $existsInMonth = Rake::query()
                        ->where('priority_number', $num)
                        ->where('siding_id', $indent->siding_id)
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->exists();
                    if ($existsInMonth) {
                        $fail('This rake priority number is already in use this month.');
                    }
                },
            ],
            'loading_date' => ['required', 'date'],
            'rake_type' => ['required', 'string', 'max:50'],
            'wagon_count' => ['required', 'integer', 'min:0'],
            'rr_expected_date' => ['nullable', 'date'],
            'placement_time' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        // Create rake from indent with form data
        $rake = new Rake;
        $rake->indent_id = $indent->id;
        $rake->siding_id = $indent->siding_id; // Use indent's siding
        $rake->rake_number = $validated['rake_number'];
        $rake->priority_number = isset($validated['rake_priority_number']) ? (int) $validated['rake_priority_number'] : ((int) Rake::query()->max('priority_number') + 1);
        $rake->loading_date = isset($validated['loading_date']) ? $validated['loading_date'] : null;
        $rake->rake_type = $validated['rake_type'] ?? null;
        $rake->wagon_count = $validated['wagon_count'] ?? 0;
        $rake->loaded_weight_mt = 0;
        $rake->predicted_weight_mt = null;
        $rake->state = 'pending';
        $rake->loading_free_minutes = (int) config('rrmcs.default_free_time_minutes', 180);
        $rake->rr_expected_date = $validated['rr_expected_date'] ?? null;
        $rake->placement_time = $validated['placement_time'] ? new DateTimeImmutable($validated['placement_time']) : null;
        $rake->created_by = $request->user()->id;
        $rake->updated_by = $request->user()->id;
        $rake->save();

        // Create wagons based on wagon count
        $wagonCount = (int) ($validated['wagon_count'] ?? 0);
        if ($wagonCount > 0) {
            for ($i = 1; $i <= $wagonCount; $i++) {
                $wagon = new Wagon;
                $wagon->rake_id = $rake->id;
                $wagon->wagon_number = "W{$i}"; // W1, W2, W3, etc.
                $wagon->wagon_sequence = $i;
                $wagon->state = 'pending';
                $wagon->save();
            }
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Rake created from completed indent successfully.');
    }
}
