<?php

namespace Database\Factories;

use App\Models\Knowledge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Knowledge>
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
        $categories = ['flower_meaning', 'care_tips', 'ordering', 'delivery', 'general'];
        return [
            'question' => $this->faker->sentence() . '?',
            'answer' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement($categories),
        ];
    }
}
