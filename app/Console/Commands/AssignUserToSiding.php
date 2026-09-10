<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Siding;
use Illuminate\Console\Command;

final class AssignUserToSiding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-siding {email : User email address} {sidingId : Siding ID or name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a user to a specific siding for RRMCS access';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $sidingIdentifier = $this->argument('sidingId');

        $user = User::where('email', $email)->first();
        
        if (! $user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        // Find siding by ID or name
        $siding = is_numeric($sidingIdentifier) 
            ? Siding::find($sidingIdentifier)
            : Siding::where('name', 'like', "%{$sidingIdentifier}%")->first();

        if (! $siding) {
            $this->error("Siding '{$sidingIdentifier}' not found.");
            $this->info('Available sidings:');
            $this->table(['ID', 'Name'], Siding::select(['id', 'name'])->get()->toArray());
            return 1;
        }

        // Check if user is already assigned
        if ($user->canAccessSiding($siding->id)) {
            $this->info("User '{$email}' already has access to siding '{$siding->name}'.");
            return 0;
        }

        // Assign user to siding
        $user->assignToSiding($siding);

        $this->info("✅ User '{$email}' has been assigned to siding '{$siding->name}'.");

        // Show user's current permissions
        $this->info("\nUser Details:");
        $this->line("Email: {$user->email}");
        $this->line("Roles: " . $user->roles->pluck('name')->implode(', '));
        $this->line("Assigned Sidings: " . $user->sidings->pluck('name')->implode(', '));

        return 0;
    }
}
