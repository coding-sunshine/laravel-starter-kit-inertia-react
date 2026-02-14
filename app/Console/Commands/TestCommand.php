<?php

declare(strict_types=1);

namespace App\Console\Commands;

use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand as CollisionTestCommand;

/**
 * Override Collision's test command so "php artisan test" defaults to max speed:
 * parallel and compact output. Use --no-parallel or --no-compact to opt out.
 */
final class TestCommand extends CollisionTestCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test
        {--without-tty : Disable output to TTY}
        {--compact : Indicates whether the compact printer should be used (default)}
        {--no-compact : Use verbose output instead of compact}
        {--coverage : Indicates whether code coverage information should be collected}
        {--min= : Indicates the minimum threshold enforcement for code coverage}
        {--p|parallel : Indicates if the tests should run in parallel (default)}
        {--no-parallel : Run tests in a single process}
        {--profile : Lists top 10 slowest tests}
        {--recreate-databases : Indicates if the test databases should be re-created}
        {--drop-databases : Indicates if the test databases should be dropped}
        {--without-databases : Indicates if database configuration should be performed}
    ';

    /**
     * Get the value of a command option. Default parallel and compact to true for max speed.
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function option($key = null)
    {
        if ($key === 'parallel') {
            return ! parent::option('no-parallel');
        }

        if ($key === 'compact') {
            return ! parent::option('no-compact');
        }

        return parent::option($key);
    }

    /**
     * Pass compact printer to parallel workers so output stays compact by default.
     *
     * @return array<string, mixed>
     */
    protected function paratestEnvironmentVariables(): array
    {
        $vars = parent::paratestEnvironmentVariables();

        if ($this->option('compact')) {
            $vars['COLLISION_PRINTER'] = 'DefaultPrinter';
            $vars['COLLISION_PRINTER_COMPACT'] = 'true';
        }

        return $vars;
    }
}
