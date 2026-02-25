<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\RakeLoadUpdated;
use App\Models\Rake;
use App\Models\RakeLoad;
use App\Services\RakeLoadStateResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class TestRakeBroadcast extends Command
{
    protected $signature = 'rake:test-broadcast {rakeId}';
    protected $description = 'Test broadcasting for rake load updates';

    public function handle(RakeLoadStateResolver $stateResolver): int
    {
        $rakeId = (int) $this->argument('rakeId');
        
        $rake = Rake::find($rakeId);
        if (!$rake) {
            $this->error("Rake with ID {$rakeId} not found.");
            return 1;
        }

        $rakeLoad = $rake->rakeLoad;
        if (!$rakeLoad) {
            $this->error("Rake load not found for rake {$rakeId}. Create a load first.");
            return 1;
        }

        $state = $this->stateResolver->resolve($rake);
        
        $this->info("Broadcasting test event for rake {$rake->rake_number} (ID: {$rakeId})");
        $this->info("Current state: " . $state['active_step']);
        
        // Dispatch the test event
        RakeLoadUpdated::dispatch($rake, $rakeLoad, $state, 'test_broadcast');
        
        $this->info("Broadcast event dispatched! Check your browser console for the event.");
        $this->info("Make sure:");
        $this->info("1. BROADCAST_CONNECTION=reverb in your .env");
        $this->info("2. Reverb server is running: php artisan reverb");
        $this->info("3. You have the rake loading page open in your browser");
        
        Log::info("Test broadcast sent for rake {$rakeId}", [
            'rake_id' => $rakeId,
            'rake_number' => $rake->rake_number,
            'state' => $state,
        ]);

        return 0;
    }
}
