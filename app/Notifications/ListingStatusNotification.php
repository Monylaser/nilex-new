<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $listing;
    protected $status;
    protected $reason;
    protected $note;

    public function __construct(Listing $listing, $status, $reason = null, $note = null)
    {
        $this->listing = $listing;
        $this->status = $status;
        $this->reason = $reason;
        $this->note = $note;
    }

    public function via($notifiable): array
    {
        // بيبعت إشعار في الداتابيز (للموقع) وإيميل
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusText = $this->status === 'published' ? '✅ تم قبول إعلانك' : '❌ تم رفض إعلانك';
        
        return (new MailMessage)
            ->subject("تحديث بخصوص إعلانك: {$this->listing->title}")
            ->greeting("أهلاً {$notifiable->name}")
            ->line("{$statusText}: \"{$this->listing->title}\"")
            ->when($this->status === 'rejected', function ($mail) {
                $reasonLabel = Listing::rejectionReasonOptions()[$this->reason] ?? $this->reason;
                return $mail->line("سبب الرفض: " . $reasonLabel)
                            ->line($this->note ? "ملاحظة إضافية: " . $this->note : "");
            })
            ->action('عرض الإعلان', url('/dashboard')) // أو رابط الإعلان لو منشور
            ->line('شكراً لاستخدامك منصة نايلكس!');
    }

    public function toArray($notifiable): array
    {
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'status' => $this->status,
            'message' => $this->status === 'published' 
                ? "تمت الموافقة على نشر إعلانك: {$this->listing->title}" 
                : "للأسف تم رفض إعلانك: {$this->listing->title}",
        ];
    }
}