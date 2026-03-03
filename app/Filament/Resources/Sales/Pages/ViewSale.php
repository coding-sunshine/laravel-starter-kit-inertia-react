<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use App\Services\PrismService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Throwable;

final class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('summarize_sale')
                ->label('Summarize sale')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(function (): void {
                    $record = $this->getRecord();
                    $this->runPrismAction(
                        "Summarize this sale in 2–4 short sentences for internal use. Include: client, project/lot, commission totals, and any key notes.\n\n"
                        . $this->saleContext($record),
                        'Sale summary',
                        'Could not generate summary.',
                    );
                }),
            Action::make('suggest_next_step')
                ->label('Suggest next step')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('gray')
                ->action(function (): void {
                    $record = $this->getRecord();
                    $this->runPrismAction(
                        "Given this sale, suggest the next best action (one short paragraph). Consider: finance due date, commission status, follow-up.\n\n"
                        . $this->saleContext($record),
                        'Next step suggestion',
                        'Could not suggest next step.',
                    );
                }),
            Action::make('draft_email_to_client')
                ->label('Draft email to client')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->action(function (): void {
                    $record = $this->getRecord();
                    $client = $record->clientContact;
                    $clientName = $client ? trim($client->first_name . ' ' . $client->last_name) : 'Client';
                    $this->runPrismAction(
                        "Draft a short, professional email to the client ({$clientName}) about this sale. Tone: helpful and clear. Do not include a subject line.\n\n"
                        . $this->saleContext($record),
                        'Email draft',
                        'Could not draft email.',
                    );
                }),
            EditAction::make(),
        ];
    }

    private function saleContext(Sale $record): string
    {
        $record->loadMissing(['clientContact', 'project', 'lot']);
        $client = $record->clientContact ? trim($record->clientContact->first_name . ' ' . $record->clientContact->last_name) : '—';
        $project = $record->project?->title ?? '—';
        $lot = $record->lot?->title ?? '—';
        $commIn = $record->comms_in_total ? number_format($record->comms_in_total, 2) : '—';
        $commOut = $record->comms_out_total ? number_format($record->comms_out_total, 2) : '—';
        $financeDue = $record->finance_due_date?->toDateString() ?? '—';
        $notes = $record->comm_in_notes ?? $record->comm_out_notes ?? '';

        return "Client: {$client}. Project: {$project}. Lot: {$lot}. Commissions in: {$commIn}. Commissions out: {$commOut}. Finance due: {$financeDue}. Notes: {$notes}";
    }

    private function runPrismAction(string $prompt, string $successTitle, string $failureTitle): void
    {
        try {
            $prism = resolve(PrismService::class);
            if (! $prism->isAvailable()) {
                Notification::make()->title('AI not configured')->body('Set your Prism/OpenRouter API key in settings.')->warning()->send();
                return;
            }
            $response = $prism->generate($prompt);
            $text = $response->text ?? '(no output)';
            Notification::make()
                ->title($successTitle)
                ->body(\Illuminate\Support\Str::limit($text, 600))
                ->success()
                ->duration(30000)
                ->send();
        } catch (Throwable $e) {
            Notification::make()->title($failureTitle)->body($e->getMessage())->danger()->send();
        }
    }
}
