<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Создаём 10 категорий
        $categories = Category::factory()->count(10)->create();

        // 2. Создаём 100 товаров, привязанных к случайным категориям
        Product::factory()->count(100)->create([
            'category_id' => fn() => $categories->random()->id,
        ]);

        $this->command->info('Created 10 categories and 100 products');
    }
}
