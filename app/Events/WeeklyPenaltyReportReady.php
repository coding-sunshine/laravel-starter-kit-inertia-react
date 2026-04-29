<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MartinPetricko\LaravelDatabaseMail\Events\Concerns\CanTriggerDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Events\Contracts\TriggersDatabaseMail;
use MartinPetricko\LaravelDatabaseMail\Recipients\Recipient;

/**
 * Dispatched every Monday morning with the previous week's penalty summary.
 */
final class WeeklyPenaltyReportReady implements TriggersDatabaseMail
{
    use CanTriggerDatabaseMail;
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array{
     *   period_label: string,
     *   total_penalties_inr: float,
     *   total_penalties_count: int,
     *   preventable_percent: float,
     *   top_operators: list<array{name: string, amount_inr: float}>,
     *   vs_prior_week_inr: float,
     *   vs_prior_week_percent: float,
     *   sidings_summary: list<array{siding_name: string, total_inr: float, count: int}>,
     * }  $reportData
     * @param  list<string>  $recipientEmails  Email addresses to receive the report
     */
    public function __construct(
        public readonly array $reportData,
        public readonly array $recipientEmails,
    ) {}

    public static function getName(): string
    {
        return 'Weekly Penalty Report';
    }

    public static function getDescription(): string
    {
        return 'Dispatched every Monday morning with the previous week\'s penalty summary, comparison to prior week, and top 3 operators by penalty amount.';
    }

    /**
     * @return array<string, Recipient<WeeklyPenaltyReportReady>>
     */
    public static function getRecipients(): array
    {
        return [
            'report_recipients' => new Recipient(
                'Users configured to receive the weekly penalty report',
                fn (WeeklyPenaltyReportReady $event): array => $event->recipientEmails,
            ),
        ];
    }
}
