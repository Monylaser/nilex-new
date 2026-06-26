<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_confirmations', function (Blueprint $table) {
            $table->id();

            // الإعلان محل البيع — restrictOnDelete يحمي تاريخ البيع/التقييم
            // (لا force-delete من الأدمن طالما يوجد تأكيد مرتبط)
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();

            // البائع (مكرر denormalized من listing.user_id لاستعلامات لوحة التحكم)
            // والمشتري — كلاهما restrictOnDelete (حذف الحساب محروس على مستوى التطبيق)
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();

            // الحالة: pending (البائع أكّد، في انتظار المشتري) → confirmed (الطرفان) أو canceled
            $table->string('status')->default('pending');

            // أوقات التأكيد — التأكيد الكامل = كلاهما غير null
            $table->timestamp('seller_confirmed_at')->nullable();
            $table->timestamp('buyer_confirmed_at')->nullable();

            // الإلغاء (البائع يدوياً من pending فقط، لا إلغاء بعد confirmed)
            $table->timestamp('canceled_at')->nullable();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // مشتري واحد لكل إعلان كحد أقصى للطلب الواحد (منع التكرار)
            $table->unique(['listing_id', 'buyer_id']);
            $table->index('status');
            $table->index('seller_id');
            $table->index('buyer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_confirmations');
    }
};
