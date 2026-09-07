<?php

namespace App\Notifications;

use App\Models\RequestedQuotes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteSubmittedAdmin extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public RequestedQuotes $quote,
    )
    {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nová poptávka')
            ->line("Jméno: {$this->quote->first_name} {$this->quote->last_name}")
            ->line("Email: {$this->quote->email}")
            ->line("Telefon: {$this->quote->phone}")
            ->line("Adresa: {$this->quote->address}")
            ->line("Výrobce kotle: {$this->quote->boiler_manufacturer}")
            ->line("Výrobní číslo: {$this->quote->boiler_serial_number}")
            ->line("Typ kotle: {$this->quote->boiler_type}")
            ->line("Zpráva: {$this->quote->message}");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
