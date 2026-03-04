<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ListUserOrganizations implements Tool
{
    public function __construct(
        private ?int $userId = null,
    ) {}

    public function description(): string
    {
        return 'List organizations the authenticated user belongs to. Use when the user asks about their organizations, teams, or workspaces.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $userId = $this->userId;
        if ($userId === null) {
            return 'User context not available.';
        }

        $user = User::query()->find($userId);
        if ($user === null) {
            return 'User not found.';
        }

        $orgs = $user->organizations()->get(['organizations.id', 'organizations.name', 'organizations.slug']);

        if ($orgs->isEmpty()) {
            return 'User is not a member of any organization.';
        }

        $lines = $orgs->map(fn ($o): string => sprintf('- %s (slug: %s)', $o->name, $o->slug));

        return 'Organizations: '."\n".$lines->implode("\n");
    }
}
