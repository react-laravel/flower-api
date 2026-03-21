<?php

namespace Database\Factories;

use App\Models\Flower;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flower>
 */
class FlowerFactory extends Factory
{
    protected $model = Flower::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['rose', 'tulip', 'lily', 'sunflower', 'orchid'];
        $category = $this->faker->randomElement($categories);

        return [
            'name' => $this->faker->words(2, true) . ' ' . ucfirst($category),
            'name_en' => ucfirst($this->faker->words(2, true)) . ' ' . ucfirst($category),
            'category' => $category,
            'price' => $this->faker->randomFloat(2, 10, 200),
            'original_price' => $this->faker->randomFloat(2, 20, 300),
            'image' => $this->faker->imageUrl(400, 400, 'flowers'),
            'description' => $this->faker->paragraph(2),
            'meaning' => $this->faker->sentence(),
            'care' => $this->faker->paragraph(1),
            'stock' => $this->faker->numberBetween(0, 100),
            'featured' => $this->faker->boolean(20),
            'holiday' => $this->faker->randomElement(['情人节', '母亲节', '春节', null]),
        ];
    }
}
