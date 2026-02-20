<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;

/**
 * In-app AI chatbot with persistent conversation history.
 * Uses OpenRouter free tier model (configurable via config ai.chat_model / AI_CHAT_MODEL).
 */
#[Model('stepfun/step-3.5-flash:free')]
final class ChatbotAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function model(): string
    {
        return config('ai.chat_model', 'stepfun/step-3.5-flash:free');
    }

    public function instructions(): string
    {
        return 'You are a helpful assistant for the Railway Rake Management Control System (RRMCS) application. '
            .'Answer questions about rakes, indents, sidings, demurrage, penalties, weighments, alerts, and general usage. '
            .'When the user message is prefixed with "Current context", use that context (user, sidings, rake counts, active alerts, rakes in loading with remaining time, penalties this month, indents, demurrage rate, current page) to give accurate, up-to-date answers specific to their data. '
            .'When the user asks how to avoid demurrage or penalties: explain that they must complete loading and close/dispatch the rake before free time ends; mention they can see remaining time per rake on the dashboard and rake detail page, and that the demurrage rate (₹ per MT per hour) is shown in the context. '
            .'When explaining a penalty or demurrage charge, use the formula: demurrage = hours over free time × weight (MT) × rate per MT per hour. '
            .'Be concise and friendly. If you do not know something, say so.';
    }
}
