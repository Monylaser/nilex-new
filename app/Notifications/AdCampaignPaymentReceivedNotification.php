<?php

namespace App\Notifications;

use App\Models\AdCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdCampaignPaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly AdCampaign $campaign) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) ($this->campaign->amount_paid ?? 0), 2);
        $currency = config('ad_pricing.currency', 'EGP');

        return (new MailMessage)
            ->subject("تم استلام دفعتك للحملة: {$this->campaign->title}")
            ->greeting("أهلاً {$notifiable->name}")
            ->line("✅ تم استلام دفعتك بنجاح للحملة \"{$this->campaign->title}\".")
            ->line("المبلغ: {$amount} {$currency}")
            ->line('حملتك الآن قيد المراجعة من فريق الإدارة. ستتلقى إشعاراً عند الموافقة.')
            ->action('عرض الحملة', url('/dashboard/ads/' . $this->campaign->id))
            ->line('شكراً لاستخدامك منصة نايلكس!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'title'       => $this->campaign->title,
            'amount_paid' => $this->campaign->amount_paid,
            'message'     => "تم استلام دفعتك للحملة: {$this->campaign->title}",
        ];
    }
}
