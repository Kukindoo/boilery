<?php

namespace App\Notifications;

use App\Models\RequestedQuotes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteSubmittedCustomer extends Notification
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
            ->subject('Potvrzení přijetí poptávky')
            ->greeting("Dobrý den {$this->quote->first_name},")
            ->line('děkujeme za Vaši poptávku.')
            ->line('Vaši poptávku jsme přijali a budeme Vás co nejdříve kontaktovat.')
            ->salutation('S pozdravem');
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
