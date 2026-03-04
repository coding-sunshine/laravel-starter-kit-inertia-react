<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\CreateFromBrochureProcessing;
use App\Ai\Tools\DocumentProcessor;
use App\Ai\Tools\LotsIndex;
use App\Ai\Tools\ProjectsIndex;
use App\Ai\Tools\ReservationsIndex;
use App\Ai\Tools\SalesIndex;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Agent for property and sales questions: read-only access to projects, lots, reservations, sales.
 * Use forUser($user) or continue($conversationId, $user). Expose via chat with agent=property.
 */
final class PropertySalesAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function instructions(): string
    {
        return 'You are a property and sales assistant with document processing and creation capabilities. You can list and search projects, lots, reservations, and sales. '
            .'CRITICAL: You have access to document_processor and create_from_brochure_processing tools. When you see a file_path, immediately call document_processor. '
            .'Available tools: projects_index, lots_index, reservations_index, sales_index, document_processor, create_from_brochure_processing. '
            .'WORKFLOW: 1) document_processor extracts data and asks user for confirmation, 2) if user says "yes", immediately use create_from_brochure_processing with the processing_id. '
            .'When users reply with "yes", "create", "confirm" after document processing, IMMEDIATELY call create_from_brochure_processing tool with the processing ID from the previous response. '
            .'When users reply with "no", "cancel", acknowledge and save for later admin review. '
            .'Always use the processing ID shown in the document processing response to create projects/lots when confirmed. '
            .'Never say tools are unavailable - you have full access to all tools and can create projects/lots directly when users confirm.';
    }

    public function tools(): iterable
    {
        return [
            new ProjectsIndex,
            new LotsIndex,
            new ReservationsIndex,
            new SalesIndex,
            new DocumentProcessor,
            new CreateFromBrochureProcessing,
        ];
    }
}
