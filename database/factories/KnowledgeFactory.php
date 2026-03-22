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

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['care', 'meaning', 'ordering', 'delivery', 'general'];
        return [
            'question' => $this->faker->sentence() . '?',
            'answer' => $this->faker->paragraph(2),
            'category' => $this->faker->randomElement($categories),
        ];
    }
}
