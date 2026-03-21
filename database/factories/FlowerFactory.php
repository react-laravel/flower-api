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

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'name_en' => $this->faker->word(),
            'category' => $this->faker->randomElement(['玫瑰', '百合', '郁金香', '向日葵', '康乃馨']),
            'price' => $this->faker->randomFloat(2, 29, 299),
            'original_price' => $this->faker->randomFloat(2, 49, 399),
            'image' => '/images/' . $this->faker->slug(2) . '.jpg',
            'description' => $this->faker->sentence(10),
            'meaning' => $this->faker->sentence(5),
            'care' => $this->faker->sentence(8),
            'stock' => $this->faker->numberBetween(0, 200),
            'featured' => $this->faker->boolean(30),
            'holiday' => $this->faker->optional(0.3)->word(),
        ];
    }
}
