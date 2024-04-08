<?php

namespace Ctrlweb\BadgeFactor2\Notifications;

use App\Models\Establishment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class ChangeEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $oldEstablishment = Establishment::find($notifiable->establishment_id);
        $newEstablishment = Establishment::find($notifiable->new_establishment_id);
        return (new MailMessage)
            ->subject(config('app.name').' - Confirmation de changement d\'adresse courriel')
            ->from(config('cadre21.mail.default_sender'))
            ->markdown('notifications.email-change', [
                'old_email' => $notifiable->email,
                'new_email' => $notifiable->new_email,
                'old_job' => $notifiable->job,
                'new_job' => $notifiable->new_job,
                'old_establishment' => $oldEstablishment->name,
                'new_establishment' => $newEstablishment->name,
                'confirmation_link' => route('confirm-email-change').'?new_email_validation_token='.$notifiable->new_email_validation_token,
            ]);
    }
}
