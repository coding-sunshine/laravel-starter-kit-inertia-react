<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiBot\Pages;

use App\Filament\Resources\AiBot\AiBotPromptCommandResource;
use App\Models\AiBotPromptCommand;
use App\Services\PrismService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Throwable;

final class ViewAiBotPromptCommand extends ViewRecord
{
    protected static string $resource = AiBotPromptCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_prompt')
                ->label('Run prompt')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(function (AiBotPromptCommand $record): void {
                    $prompt = $record->prompt ?? '';
                    if ($prompt === '') {
                        Notification::make()
                            ->title('No prompt')
                            ->body('This command has no prompt text.')
                            ->danger()
                            ->send();
                        return;
                    }
                    try {
                        $prism = resolve(PrismService::class);
                        if (! $prism->isAvailable()) {
                            Notification::make()
                                ->title('AI not configured')
                                ->body('Set your Prism/OpenRouter API key in settings.')
                                ->warning()
                                ->send();
                            return;
                        }
                        $response = $prism->generate($prompt);
                        $text = $response->text ?? '(no output)';
                        Notification::make()
                            ->title('Prompt result')
                            ->body(\Illuminate\Support\Str::limit($text, 500))
                            ->success()
                            ->duration(15000)
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Run failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            EditAction::make(),
        ];
    }
}
