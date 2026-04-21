<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(rand(2, 4), true),
            'price' => round(fake()->randomFloat(2, 9.99, 2999.99), 2),
            'category_id' => Category::inRandomOrder()->first()?->id,
            'in_stock' => fake()->boolean(85),
            'rating' => round(fake()->randomFloat(1, 0, 5), 1),
            'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'updated_at' => fn($attrs) => fake()->dateTimeBetween($attrs['created_at'], 'now'),
        ];
    }

    // Состояния для гибкой генерации
    public function inStock(): static
    {
        return $this->state(fn($attrs) => ['in_stock' => true]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn($attrs) => ['in_stock' => false]);
    }

    public function highlyRated(): static
    {
        return $this->state(fn($attrs) => ['rating' => round(fake()->randomFloat(1, 4.0, 5.0), 1)]);
    }

    public function withCategory(int $categoryId): static
    {
        return $this->state(fn($attrs) => ['category_id' => $categoryId]);
    }
}
