<?php

namespace Database\Factories;

use App\Models\Knowledge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Knowledge>
 */
class KnowledgeFactory extends Factory
{
    protected $model = Knowledge::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence(8) . '？',
            'answer' => $this->faker->paragraph(3),
            'category' => $this->faker->randomElement(['花语', '养护', '配送', '订购', '节日']),
        ];
    }
}
