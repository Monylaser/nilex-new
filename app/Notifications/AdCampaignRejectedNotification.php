<?php

namespace App\Notifications;

use App\Models\AdCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdCampaignRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AdCampaign $campaign,
        public readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("تم رفض حملتك الإعلانية: {$this->campaign->title}")
            ->greeting("أهلاً {$notifiable->name}")
            ->line("❌ للأسف تم رفض حملتك الإعلانية \"{$this->campaign->title}\".")
            ->line("سبب الرفض: {$this->reason}")
            ->action('عرض الحملة', url('/dashboard/ads/' . $this->campaign->id))
            ->line('شكراً لاستخدامك منصة نايلكس!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'title'       => $this->campaign->title,
            'reason'      => $this->reason,
            'message'     => "تم رفض حملتك الإعلانية: {$this->campaign->title}",
        ];
    }
}
