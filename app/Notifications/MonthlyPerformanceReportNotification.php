<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class MonthlyPerformanceReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $reportPath,
        protected string $periodLabel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('تقرير الأداء الشهري — ' . $this->periodLabel)
            ->greeting("أهلاً {$notifiable->name}")
            ->line('إليك ملخص أداء إعلاناتك للشهر الماضي.')
            ->line('يمكنك الاطلاع على التفاصيل في الملف المرفق.')
            ->action('لوحة التحكم', url('/dashboard'));

        if (Storage::disk('local')->exists($this->reportPath)) {
            $mail->attach(Storage::disk('local')->path($this->reportPath), [
                'as'   => basename($this->reportPath),
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
