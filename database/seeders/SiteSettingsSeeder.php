<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $settings = [
            // Hero section
            'hero_title' => '花好月圆',
            'hero_subtitle' => '用花讲述爱的故事',
            'hero_description' => '精心挑选每一朵花，为您传递爱与温暖。我们提供最新鲜的花材，最专业的花艺设计。',
            'hero_cta_text' => '探索花品',
            'hero_cta_link' => '/flowers',

            // Features section
            'feature_1_icon' => 'Heart',
            'feature_1_title' => '精心挑选',
            'feature_1_desc' => '每日新鲜采购',
            'feature_2_icon' => 'Gift',
            'feature_2_title' => '精美包装',
            'feature_2_desc' => '专业花艺设计',
            'feature_3_icon' => 'Truck',
            'feature_3_title' => '快速配送',
            'feature_3_desc' => '2小时送达',
            'feature_4_icon' => 'Star',
            'feature_4_title' => '品质保证',
            'feature_4_desc' => '不满意可退换',

            // Contact
            'contact_phone' => '010-12345678',
            'contact_address' => '北京市朝阳区建国路88号',
            'contact_hours' => '8:00-21:00',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
