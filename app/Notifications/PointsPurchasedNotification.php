<?php

namespace App\Notifications;

use App\Models\PointPlan;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PointsPurchasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected PointPlan $plan,
        protected Transaction $transaction,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $points = (int) $this->plan->points;
        $amount = number_format((float) $this->transaction->amount, 2);

        return (new MailMessage)
            ->subject('تم شحن رصيد النقاط بنجاح')
            ->greeting("أهلاً {$notifiable->name}")
            ->line("تم إضافة {$points} نقطة إلى محفظتك.")
            ->line("المبلغ المدفوع: {$amount} ج.م")
            ->action('عرض سجل النقاط', url('/points/history'))
            ->line('شكراً لاستخدامك منصة نايلكس!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'plan_id'        => $this->plan->id,
            'points'         => (int) $this->plan->points,
            'amount'         => (float) $this->transaction->amount,
            'message'        => 'تم شحن ' . (int) $this->plan->points . ' نقطة إلى محفظتك بنجاح.',
        ];
    }
}
