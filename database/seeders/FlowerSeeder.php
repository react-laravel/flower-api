<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Flower;
use App\Models\Category;
use App\Models\Knowledge;

class FlowerSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = [
            ['name' => '玫瑰', 'slug' => 'rose', 'icon' => '🌹', 'description' => '爱情之花'],
            ['name' => '百合', 'slug' => 'lily', 'icon' => '🌸', 'description' => '纯洁高雅'],
            ['name' => '康乃馨', 'slug' => 'carnation', 'icon' => '🌺', 'description' => '母爱之花'],
            ['name' => '向日葵', 'slug' => 'sunflower', 'icon' => '🌻', 'description' => '阳光积极'],
            ['name' => '郁金香', 'slug' => 'tulip', 'icon' => '🌷', 'description' => '高贵典雅'],
            ['name' => '绿植', 'slug' => 'plant', 'icon' => '🌿', 'description' => '清新自然'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Flowers
        $flowers = [
            [
                'name' => '红玫瑰花束',
                'name_en' => 'Red Rose Bouquet',
                'category' => 'rose',
                'price' => 299,
                'original_price' => 399,
                'image' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800',
                'description' => '11朵精选红玫瑰，搭配尤加利叶和满天星，象征永恒的爱情。',
                'meaning' => '热恋、我爱你、永恒的爱',
                'care' => '保持水温18-20°C，每天换水，斜剪花茎，避免阳光直射',
                'stock' => 50,
                'featured' => true,
            ],
            [
                'name' => '粉百合束',
                'name_en' => 'Pink Lily Bouquet',
                'category' => 'lily',
                'price' => 358,
                'image' => 'https://images.unsplash.com/photo-15227647269939-2e0753d64975?w=800',
                'description' => '9朵粉百合花束，象征百年好合，纯洁高雅。',
                'meaning' => '百年好合、纯洁、高雅',
                'care' => '保持水温15-18°C，及时摘除花蕊，避免香气过浓',
                'stock' => 30,
                'featured' => true,
            ],
            [
                'name' => '康乃馨花束',
                'name_en' => 'Carnations Bouquet',
                'category' => 'carnation',
                'price' => 168,
                'image' => 'https://images.unsplash.com/photo-1527513879417-1466679446b1?w=800',
                'description' => '粉康乃馨花束，送给母亲最温暖的祝福。',
                'meaning' => '母爱、温馨、祝福',
                'care' => '保持水质清洁，每天换水，可存活2-3周',
                'stock' => 80,
                'featured' => true,
            ],
            [
                'name' => '向日葵花束',
                'name_en' => 'Sunflower Bouquet',
                'category' => 'sunflower',
                'price' => 228,
                'image' => 'https://images.unsplash.com/photo-1470509037663-253ce784d5be?w=800',
                'description' => '5朵向日葵，充满阳光活力，适合送给朋友。',
                'meaning' => '阳光、积极、信念',
                'care' => '保持充足水分，每天换水，喜阳但避免暴晒',
                'stock' => 40,
                'featured' => false,
            ],
            [
                'name' => '蓝色妖姬',
                'name_en' => 'Blue Rose',
                'category' => 'rose',
                'price' => 588,
                'original_price' => 688,
                'image' => 'https://images.unsplash.com/photo-1550955295-a855bceb2928?w=800',
                'description' => '进口蓝玫瑰，独特的蓝色系，象征不可能的爱。',
                'meaning' => '奇迹、不可能、珍惜',
                'care' => '保持低温保存，每天换水，可存活较长时间',
                'stock' => 15,
                'featured' => true,
                'holiday' => 'valentine',
            ],
        ];

        foreach ($flowers as $flower) {
            Flower::create($flower);
        }

        // Knowledge base
        $knowledge = [
            [
                'question' => '鲜花如何保鲜？',
                'answer' => "1. 每天换水，保持水质清洁\n2. 斜剪花茎，帮助花朵吸水\n3. 避免阳光直射和空调直吹\n4. 可以在水中加入少量糖或保鲜剂\n5. 及时摘除枯萎的花瓣和叶子",
                'category' => 'care',
            ],
            [
                'question' => '玫瑰的花语是什么？',
                'answer' => '红玫瑰代表热恋和永恒的爱；粉玫瑰代表初恋和感动；白玫瑰代表纯洁和尊敬；黄玫瑰代表友谊和祝福；蓝玫瑰代表奇迹和不可能。',
                'category' => 'meaning',
            ],
            [
                'question' => '如何订花？',
                'answer' => "您可以通过以下方式订花：\n1. 直接在网站下单\n2. 拨打客服电话：010-12345678\n3. 微信搜索「花好月圆花店」小程序\n我们提供同城配送服务，2小时内送达。",
                'category' => 'order',
            ],
            [
                'question' => '配送范围和时间？',
                'answer' => '我们提供北京市内免费配送服务，偏远地区需额外支付配送费。当日下单，可享受：\n- 上午订单：当日下午送达\n- 下午订单：晚上送达\n- 紧急配送：2小时内送达（需加急费用）',
                'category' => 'delivery',
            ],
            [
                'question' => '如何养护绿植？',
                'answer' => '1. 根据植物种类决定浇水频率\n2. 大多数室内植物每周浇水1-2次\n3. 避免积水，保持土壤排水良好\n4. 定期施肥，生长季每2周一次\n5. 保持适当光照，避免暴晒',
                'category' => 'care',
            ],
            [
                'question' => '支持哪些支付方式？',
                'answer' => "我们支持以下支付方式：\n- 微信支付\n- 支付宝\n- 银行卡转账\n- 到店付款\n支持花呗分期付款（限大额订单）",
                'category' => 'payment',
            ],
        ];

        foreach ($knowledge as $item) {
            Knowledge::create($item);
        }
    }
}
