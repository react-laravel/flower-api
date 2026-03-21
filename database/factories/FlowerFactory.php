<?php

namespace Database\Factories;

use App\Models\Flower;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flower>
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
        $categories = ['rose', 'tulip', 'lily', 'daisy', 'sunflower'];
        $holidays = ['valentine', 'mother_day', 'birthday', 'anniversary', null];

        return [
            'name' => $this->faker->words(2, true) . ' ' . $this->faker->randomElement(['Rose', 'Lily', 'Tulip', 'Daisy']),
            'name_en' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement($categories),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'original_price' => $this->faker->randomFloat(2, 15, 150),
            'image' => $this->faker->imageUrl(400, 400, 'flowers'),
            'description' => $this->faker->paragraph(),
            'meaning' => $this->faker->sentence(),
            'care' => $this->faker->paragraph(),
            'stock' => $this->faker->numberBetween(0, 500),
            'featured' => $this->faker->boolean(30),
            'holiday' => $this->faker->randomElement($holidays),
        ];
    }

    /**
     * Indicate that the flower is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }

    /**
     * Indicate that the flower is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
