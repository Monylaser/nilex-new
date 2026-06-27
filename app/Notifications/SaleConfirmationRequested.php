<?php

namespace App\Notifications;

use App\Models\SaleConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * يُرسَل للمشتري عندما يسجّل البائع عملية بيع له (SaleConfirmation pending)،
 * يدعوه لتأكيد الشراء من /dashboard/purchases ثم تقييم البائع.
 *
 * مترجم بالكامل (عربي/إنجليزي): النصوص تُحلّ بلغة المشتري ($notifiable->locale)
 * وقت الإرسال — على عكس الإشعارات القديمة التي كانت تجمّد نصاً عربياً.
 * data['url'] يجعل عنصر الجرس قابلاً للنقر (الجرس مُعدّل ليدعمه، متوافق رجعياً).
 */
class SaleConfirmationRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly SaleConfirmation $saleConfirmation) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $this->localeFor($notifiable);
        $sc     = $this->saleConfirmation->loadMissing(['listing', 'seller']);

        $listingTitle = $sc->listing?->title ?? '—';
        $sellerName   = $sc->seller?->name ?? '—';

        return (new MailMessage)
            ->subject(__('server.sale_confirmation_notif.subject', [], $locale))
            ->greeting(__('server.sale_confirmation_notif.greeting', ['name' => $notifiable->name], $locale))
            ->line(__('server.sale_confirmation_notif.line1', [
                'seller' => $sellerName,
                'title'  => $listingTitle,
            ], $locale))
            ->line(__('server.sale_confirmation_notif.line2', [], $locale))
            ->action(
                __('server.sale_confirmation_notif.action', [], $locale),
                route('dashboard.purchases')
            )
            ->line(__('server.sale_confirmation_notif.line3', [], $locale));
    }

    public function toArray(object $notifiable): array
    {
        $locale = $this->localeFor($notifiable);
        $sc     = $this->saleConfirmation->loadMissing(['listing', 'seller']);

        return [
            'sale_confirmation_id' => $sc->id,
            'listing_id'           => $sc->listing_id,
            'listing_title'        => $sc->listing?->title,
            'seller_name'          => $sc->seller?->name,
            // نص جاهز للجرس (متوافق مع data['message'] الحالي)
            'message' => __('server.sale_confirmation_notif.db_message', [
                'seller' => $sc->seller?->name ?? '—',
                'title'  => $sc->listing?->title ?? '—',
            ], $locale),
            // يجعل عنصر الجرس قابلاً للنقر إلى شاشة "مشترياتي"
            'url' => route('dashboard.purchases'),
        ];
    }

    // يعتمد لغة المشتري المحفوظة (users.locale) إن وُجدت، وإلا لغة التطبيق الحالية
    protected function localeFor(object $notifiable): string
    {
        return $notifiable->locale ?? app()->getLocale();
    }
}
