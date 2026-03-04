<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\CreateFromBrochureProcessingAction;
use App\Models\BrochureProcessing;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class CreateFromBrochureProcessing implements Tool
{
    public function description(): string
    {
        return 'Create a project or lot from processed brochure data when user confirms. Takes the processing ID and creates the actual project/lot record.';
    }

    public function handle(Request $request): Stringable|string
    {
        $processingId = $request->integer('processing_id');
        $userConfirmation = strtolower(trim((string) $request->string('confirmation', '')));

        if (empty($processingId)) {
            return 'Error: Processing ID is required.';
        }

        // Find the processing record
        $processing = BrochureProcessing::find($processingId);

        if (!$processing) {
            return "Error: No brochure processing found with ID {$processingId}.";
        }

        if ($processing->status === 'approved') {
            return "✅ This {$processing->type} has already been created from processing ID {$processingId}.";
        }

        // Check user confirmation
        if (!in_array($userConfirmation, ['yes', 'y', 'create', 'confirm'])) {
            return "⏸️ **Creation cancelled.** The extracted {$processing->type} information has been saved for later review.\n\n" .
                   "You can ask an admin to review Processing ID {$processingId} in the Filament admin panel, or say **'yes'** if you'd like me to create it now.";
        }

        try {
            // Use the action to create the project/lot
            $action = new CreateFromBrochureProcessingAction();
            $result = $action->handle($processing);

            if ($result['success']) {
                return "🎉 **{$processing->type} created successfully!**\n\n" .
                       "**✅ Created:** {$result['title']}\n" .
                       "**🔗 Type:** " . ucfirst($processing->type) . "\n" .
                       "**📋 Record ID:** {$result['id']}\n\n" .
                       "The {$processing->type} is now available in your system and can be found in the " .
                       ($processing->type === 'project' ? 'Projects' : 'Lots') . " section.";
            } else {
                return "❌ **Creation failed:** " . ($result['message'] ?? 'Unknown error occurred');
            }

        } catch (\Exception $e) {
            return "❌ **Error creating {$processing->type}:** {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'processing_id' => $schema->integer()->description('The ID of the brochure processing record to create from.'),
            'confirmation' => $schema->string()->description('User confirmation: "yes", "no", "create", etc.')->default(''),
        ];
    }
}
