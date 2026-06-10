<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class CookiePolicySeeder extends Seeder
{
    public function run(): void
    {
        LegalPage::updateOrCreate(
            ['slug' => 'cookies-policy'],
            [
                'title' => [
                    'ar' => 'سياسة ملفات الارتباط',
                    'en' => 'Cookies Policy',
                ],
                'content' => [
                    'ar' => '<h2>ما هي ملفات الارتباط؟</h2>
<p>ملفات الارتباط (Cookies) هي ملفات نصية صغيرة يتم تخزينها على جهازك عند زيارة موقعنا. تساعدنا هذه الملفات في تحسين تجربتك وتقديم محتوى مخصص.</p>

<h2>كيف نستخدم ملفات الارتباط؟</h2>
<p>نستخدم ملفات الارتباط للأغراض التالية:</p>
<ul>
    <li><strong>الضرورية:</strong> تضمن عمل الموقع بشكل صحيح وأمان جلستك.</li>
    <li><strong>الأداء:</strong> تساعدنا على فهم كيفية تفاعل الزوار مع الموقع لتحسين تجربتهم.</li>
    <li><strong>التسويق:</strong> تتيح لنا عرض إعلانات ذات صلة باهتماماتك.</li>
    <li><strong>التحليل:</strong> تحليل حركة الزيارات وسلوك المستخدمين لتطوير خدماتنا.</li>
</ul>

<h2>التحكم في ملفات الارتباط</h2>
<p>يمكنك التحكم في ملفات الارتباط وحذفها من خلال إعدادات متصفحك. يُرجى ملاحظة أن تعطيل بعض ملفات الارتباط قد يؤثر على وظائف الموقع.</p>

<h2>ملفات الارتباط التابعة لطرف ثالث</h2>
<p>قد نستخدم خدمات طرف ثالث مثل Google Analytics وخدمات التواصل الاجتماعي التي تضع ملفات الارتباط الخاصة بها.</p>

<h2>تحديثات هذه السياسة</h2>
<p>نحتفظ بحق تحديث سياسة ملفات الارتباط في أي وقت. سنُعلمك بأي تغييرات جوهرية عبر إشعار بارز على الموقع.</p>

<h2>تواصل معنا</h2>
<p>إذا كان لديك أي استفسار حول استخدامنا لملفات الارتباط، يُرجى <a href="/contact">التواصل معنا</a>.</p>',

                    'en' => '<h2>What Are Cookies?</h2>
<p>Cookies are small text files stored on your device when you visit our website. They help us improve your experience and deliver personalized content.</p>

<h2>How We Use Cookies</h2>
<p>We use cookies for the following purposes:</p>
<ul>
    <li><strong>Essential:</strong> Ensure the website works correctly and your session is secure.</li>
    <li><strong>Performance:</strong> Help us understand how visitors interact with the site to improve their experience.</li>
    <li><strong>Marketing:</strong> Allow us to show ads relevant to your interests.</li>
    <li><strong>Analytics:</strong> Analyze traffic and user behavior to improve our services.</li>
</ul>

<h2>Controlling Cookies</h2>
<p>You can control and delete cookies through your browser settings. Please note that disabling certain cookies may affect website functionality.</p>

<h2>Third-Party Cookies</h2>
<p>We may use third-party services such as Google Analytics and social media platforms that place their own cookies.</p>

<h2>Policy Updates</h2>
<p>We reserve the right to update this cookie policy at any time. We will notify you of any significant changes via a prominent notice on the website.</p>

<h2>Contact Us</h2>
<p>If you have any questions about our use of cookies, please <a href="/contact">contact us</a>.</p>',
                ],
                'is_active'       => true,
                'seo_title'       => null,
                'seo_description' => null,
                'meta_keywords'   => 'cookies, سياسة الكوكيز, ملفات الارتباط',
            ]
        );

        $this->command->info('✅  Cookies Policy seeded (slug: cookies-policy).');
    }
}
