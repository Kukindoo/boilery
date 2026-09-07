<?php

namespace App\Notifications;

use App\Models\RequestedQuotes;
use Illuminate\Bus\Queueable;
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
    ) {}

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
        $mail = (new MailMessage)
            ->subject('Nová poptávka')
            ->line('Jméno: ' . $this->quote->first_name . ' ' . $this->quote->last_name)
            ->line('Email: ' . $this->quote->email)
            ->line('Telefon: ' . $this->quote->phone)
            ->line('Adresa: ' . ($this->quote->address ?? 'Neuvedeno'))
            ->line('Výrobce kotle: ' . ($this->quote->boiler_manufacturer ?? 'Neuvedeno'))
            ->line('Výrobní číslo: ' . ($this->quote->boiler_serial_number ?? 'Neuvedeno'))
            ->line('Typ kotle: ' . ($this->quote->boiler_type ?? 'Neuvedeno'))
            ->line('Záruka: ' . ($this->quote->under_warranty ? 'Ano' : 'Ne'))
            ->line('Zpráva:')
            ->line($this->quote->message);

        if ($this->quote->label_file_path) {
            $extension = pathinfo(
                $this->quote->label_file_path,
                PATHINFO_EXTENSION
            );

            $mail->attachFromStorage(
                $this->quote->label_file_path,
                "Stitek-kotle.{$extension}"
            );
        }

        if ($this->quote->warranty_file_path) {
            $extension = pathinfo(
                $this->quote->warranty_file_path,
                PATHINFO_EXTENSION
            );
            $mail->attachFromStorage(
                $this->quote->warranty_file_path,
                "Zarucni-list-kotle.{$extension}"
            );
        }

        if ($this->quote->receipt_file_path) {
            $extension = pathinfo(
                $this->quote->receipt_file_path,
                PATHINFO_EXTENSION
            );
            $mail->attachFromStorage(
                $this->quote->receipt_file_path,
                "Kupni-dokument-kotle.{$extension}"
            );
        }

        return $mail;
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
