<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Term;
use App\Models\PrivacyPolicy;
use App\Models\AboutApp;
use App\Models\Faq;
use App\Models\IssueType;

class StaticPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Terms and Conditions
        Term::updateOrCreate(['id' => 1], [
            'title_ar' => 'الشروط والاحكام',
            'title_en' => 'Terms and Conditions',
            'content_ar' => '<p>مرحبا بك في تطبيق سوالف. باستخدامك لهذا التطبيق، فإنك توافق على الالتزام بالشروط والأحكام التالية...</p>',
            'content_en' => '<p>Welcome to Sawalef. By using this application, you agree to comply with the following terms and conditions...</p>',
        ]);

        // 2. Privacy Policy
        PrivacyPolicy::updateOrCreate(['id' => 1], [
            'title_ar' => 'سياسة الخصوصية',
            'title_en' => 'Privacy Policy',
            'content_ar' => '<p>نحن نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية المعالجة من خلال التطبيق...</p>',
            'content_en' => '<p>We respect your privacy and are committed to protecting your personal data processed through the application...</p>',
        ]);

        // 3. About App
        AboutApp::updateOrCreate(['id' => 1], [
            'title_ar' => 'عن التطبيق',
            'title_en' => 'About App',
            'content_ar' => '<p>تطبيق سوالف هو منصة اجتماعية تهدف إلى ربط الأشخاص بناءً على الهوايات والاهتمامات المشتركة...</p>',
            'content_en' => '<p>Sawalef is a social platform aiming to connect people based on shared hobbies and interests...</p>',
        ]);

        // 4. FAQs
        $faqs = [
            [
                'question_ar' => 'كيف يمكنني إنشاء حساب؟',
                'question_en' => 'How can I create an account?',
                'answer_ar' => '<p>يمكنك إنشاء حساب باستخدام رقم الهاتف الخاص بك وتفعيل الكود المرسل.</p>',
                'answer_en' => '<p>You can create an account using your phone number and verifying the sent code.</p>',
            ],
            [
                'question_ar' => 'هل التطبيق مجاني؟',
                'question_en' => 'Is the app free?',
                'answer_ar' => '<p>نعم، التطبيق مجاني للاستخدام الأساسي مع وجود باقات مميزة.</p>',
                'answer_en' => '<p>Yes, the app is free for basic use with premium packages available.</p>',
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question_ar' => $faq['question_ar']],
                $faq
            );
        }

        // 5. Issue Types (For Support)
        $issueTypes = [
            ['name_ar' => 'مشكلة تقنية', 'name_en' => 'Technical Issue'],
            ['name_ar' => 'بلاغ عن محتوى', 'name_en' => 'Report Content'],
            ['name_ar' => 'اقتراح جديد', 'name_en' => 'New Suggestion'],
            ['name_ar' => 'استفسار عام', 'name_en' => 'General Inquiry'],
            ['name_ar' => 'مشكلة في الدفع', 'name_en' => 'Payment Issue'],
        ];

        foreach ($issueTypes as $type) {
            IssueType::updateOrCreate(
                ['name_ar' => $type['name_ar']],
                $type
            );
        }
    }
}
