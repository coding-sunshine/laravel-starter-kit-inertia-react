<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\AlertsTool;
use App\Ai\Tools\DisputeAnalysisTool;
use App\Ai\Tools\IndentStatusTool;
use App\Ai\Tools\PenaltySummaryTool;
use App\Ai\Tools\PredictionsTool;
use App\Ai\Tools\RakeStatusTool;
use App\Ai\Tools\SidingPerformanceTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * In-app AI chatbot with persistent conversation history and database tools.
 * Uses OpenRouter or OpenAI model (configurable via config ai.chat_model / AI_CHAT_MODEL).
 */
#[MaxSteps(5)]
#[Timeout(120)]
final class ChatbotAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    /**
     * @param  array<int>  $sidingIds  The siding IDs the user has access to (security boundary)
     */
    public function __construct(private readonly array $sidingIds = []) {}

    public function model(): string
    {
        return config('ai.chat_model', 'openai/gpt-4o-mini');
    }

    public function instructions(): string
    {
        return 'You are a helpful RRMCS (Railway Rake Management Control System) assistant with access to real-time database tools. '
            .'IMPORTANT: When users ask specific questions about penalties, rakes, sidings, indents, alerts, disputes, or performance metrics, '
            .'USE YOUR TOOLS to query real data instead of guessing. Tools give you live data scoped to the user\'s accessible sidings. '
            .'Answer in terms of users\' old Excel/paper workflow when explaining features. '
            .'Workflow map: indent register → Indents page; rake status Excel → Rakes page (live state tracking); '
            .'penalty calculation worksheet → Penalties page (auto-calculation); RR filing cabinet → Railway Receipts (PDF upload); '
            .'gate register → Road Dispatch arrivals; reconciliation vlookup → Reconciliation page; daily/monthly report Excel → Reports page (CSV export). '
            .'Glossary: FNR = Freight Note Reference (unique ID on RR); TXR = Train Examination Report (rake positioning doc); '
            .'MT = Metric Tonnes; Demurrage = penalty for exceeding free loading time; Free time = hours allowed before demurrage; '
            .'e-Demand = railway electronic indent booking system; IMWB = In-Motion Weighbridge; RR = Railway Receipt; '
            .'Wagon table = structured list of wagon numbers, types, weights from an RR. '
            .'When user message has "Current context", use that data for quick answers but prefer tools for specific queries. '
            .'Demurrage formula: hours over free time x weight (MT) x rate per MT per hour. '
            .'Format currency as ₹ with commas (e.g. ₹1,25,000). '
            .'Be concise, friendly, and say so if you do not know something.';
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new PenaltySummaryTool($this->sidingIds),
            new RakeStatusTool($this->sidingIds),
            new SidingPerformanceTool($this->sidingIds),
            new AlertsTool($this->sidingIds),
            new DisputeAnalysisTool($this->sidingIds),
            new IndentStatusTool($this->sidingIds),
            new PredictionsTool($this->sidingIds),
        ];
    }
}
