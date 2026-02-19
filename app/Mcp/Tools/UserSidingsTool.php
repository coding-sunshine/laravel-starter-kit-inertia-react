<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class UserSidingsTool extends Tool
{
    protected string $name = 'user_sidings';

    protected string $title = 'Get User Sidings';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get all sidings that a specific user has access to, including their role and primary siding assignment.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $userId = $request->get('user_id');
        if ($userId === null) {
            return Response::error('Missing required parameter: user_id');
        }

        $user = User::query()
            ->with('sidings')
            ->find($userId);

        if ($user === null) {
            return Response::error("User with id {$userId} not found.");
        }

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->toArray(),
            ],
            'sidings' => $user->sidings->map(fn ($siding) => [
                'id' => $siding->id,
                'name' => $siding->name,
                'code' => $siding->code,
                'location' => $siding->location,
                'is_primary' => $siding->pivot->is_primary,
            ])->toArray(),
        ];

        return Response::json($data);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('User ID')->required(),
        ];
    }
}
