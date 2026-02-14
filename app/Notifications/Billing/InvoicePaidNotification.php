<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Billing\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvoicePaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice
    ) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invoice '.$this->invoice->number.' paid')
            ->line('Your invoice '.$this->invoice->number.' has been paid.')
            ->line('Total: '.$this->invoice->currency.' '.round($this->invoice->total / 100, 2));
    }
}
