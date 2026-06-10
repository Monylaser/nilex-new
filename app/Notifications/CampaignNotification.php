<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Campaign $campaign) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->campaign->title)
            ->greeting('مرحباً، ' . ($notifiable->name ?? ''))
            ->line($this->campaign->message)
            ->action('زيارة المنصة', url('/'))
            ->salutation('فريق Nilex');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'title'       => $this->campaign->title,
            'message'     => $this->campaign->message,
        ];
    }
}
