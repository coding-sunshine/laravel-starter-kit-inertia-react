<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AutomationRule;
use App\Models\User;
use App\Notifications\GenericDatabaseNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Execute each action defined in an automation rule.
 */
final readonly class ProcessAutomationRuleAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(AutomationRule $rule, array $payload): void
    {
        foreach ($rule->actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            try {
                $this->executeAction($action, $payload, $rule);
            } catch (Throwable $e) {
                Log::warning('Automation rule action failed', [
                    'rule_id' => $rule->id,
                    'action_type' => $action['type'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $rule->increment('run_count');
        $rule->last_run_at = now();
        $rule->saveQuietly();
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $payload
     */
    private function executeAction(array $action, array $payload, AutomationRule $rule): void
    {
        $type = $action['type'] ?? null;
        $config = $action['config'] ?? [];

        match ($type) {
            'send_notification' => $this->sendNotification($config, $payload, $rule),
            'create_task' => $this->createTask($config, $payload),
            'update_field' => $this->updateField($config, $payload),
            'send_email' => $this->sendEmail($config, $payload),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     */
    private function sendNotification(array $config, array $payload, AutomationRule $rule): void
    {
        $userId = $config['user_id'] ?? null;

        if ($userId === null) {
            return;
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            return;
        }

        $message = $config['message'] ?? "Automation rule '{$rule->name}' was triggered.";
        $user->notify(new GenericDatabaseNotification(
            title: 'Automation Rule Triggered',
            message: $message,
            type: 'info',
        ));
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     */
    private function createTask(array $config, array $payload): void
    {
        // Stub: future implementation can create tasks via CreateTaskAction
        Log::info('Automation: create_task action triggered', ['config' => $config, 'payload' => $payload]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     */
    private function updateField(array $config, array $payload): void
    {
        // Stub: future implementation can update model fields
        Log::info('Automation: update_field action triggered', ['config' => $config, 'payload' => $payload]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     */
    private function sendEmail(array $config, array $payload): void
    {
        // Stub: future implementation can dispatch a mail job
        Log::info('Automation: send_email action triggered', ['config' => $config, 'payload' => $payload]);
    }
}
