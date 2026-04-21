<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/products (Pagination)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_list_products_with_pagination(): void
    {
        Product::factory()->count(25)->create();

        $response = $this->getJson('/api/products?page=2&per_page=10');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'links' => ['first', 'last', 'prev', 'next'],
                     'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
                 ]);

        $this->assertEquals(10, $response->json('meta.per_page'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertCount(5, $response->json('data')); // Page 2 of 25 with 10 per page
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/products?q=phone (Full-text Search)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_search_products_by_name(): void
    {
        Product::factory()->create(['name' => 'Wireless Gaming Phone']);
        Product::factory()->create(['name' => 'Bluetooth Speaker']);
        Product::factory()->create(['name' => 'USB Cable']);

        $response = $this->getJson('/api/products?q=phone');
        $items = $response->json('data.items');
        
        $response->assertStatus(200);
        $this->assertCount(1, $items);
        $this->assertEquals('Wireless Gaming Phone', $items[0]['name']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/products?price_from=100&price_to=500 (Price Filter)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_filter_products_by_price_range(): void
    {
        Product::factory()->create(['price' => 50]);
        Product::factory()->create(['price' => 250]);
        Product::factory()->create(['price' => 600]);

        $response = $this->getJson('/api/products?price_from=100&price_to=500');

        $items = $response->json('data.items');
        $response->assertStatus(200);
        $this->assertCount(1, $items);
        $this->assertEquals(250, $items[0]['price']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/products?category_id=3&in_stock=true (Category + Stock Filter)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_filter_products_by_category_and_stock(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create(['category_id' => $category->id, 'in_stock' => true]);
        Product::factory()->create(['category_id' => $category->id, 'in_stock' => false]);
        Product::factory()->create(['category_id' => null, 'in_stock' => true]);

        $response = $this->getJson("/api/products?category_id={$category->id}&in_stock=1");

        $items = $response->json('data.items');
        $response->assertStatus(200);
        $this->assertCount(1, $items);
        $this->assertEquals($category->id, $items[0]['category_id']);

    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/products?sort=rating_desc (Sorting)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_sort_products_by_rating_descending(): void
    {
        Product::factory()->create(['name' => 'Low', 'rating' => 2.5]);
        Product::factory()->create(['name' => 'High', 'rating' => 4.8]);
        Product::factory()->create(['name' => 'Medium', 'rating' => 3.7]);

        $response = $this->getJson('/api/products?sort=rating_desc');
        /*
        file_put_contents(
            storage_path('logs/debug_response.json'),
            json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        */
        $response->assertStatus(200);
        $items = $response->json('data.items');

        $this->assertEquals('High', $items[0]['name']);
        $this->assertEquals('Medium', $items[1]['name']);
        $this->assertEquals('Low', $items[2]['name']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/products/42 (Show Single)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_get_single_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $product->id)
                 ->assertJsonPath('data.name', $product->name);
    }

    public function test_get_non_existent_product_returns_404(): void
    {
        $response = $this->getJson('/api/products/999');
        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/products (Create)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_create_product(): void
    {
        $category = Category::factory()->create();
        $payload = [
            'name' => 'New Item',
            'price' => 99.99,
            'category_id' => $category->id,
            'in_stock' => true,
            'rating' => 0,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'New Item')
                 ->assertJsonPath('data.price', 99.99);

        $this->assertDatabaseHas('products', ['name' => 'New Item']);
    }

    public function test_create_product_fails_validation(): void
    {
        $response = $this->postJson('/api/products', ['name' => '', 'price' => 'not_a_number']);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'price']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/products/42 (Update)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_update_product(): void
    {
        $product = Product::factory()->create(['price' => 100, 'in_stock' => true]);

        $payload = ['price' => 79.99, 'in_stock' => false];

        $response = $this->putJson("/api/products/{$product->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('data.price', 79.99)
                 ->assertJsonPath('data.in_stock', false);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 79.99, 'in_stock' => false]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /api/products/42 (Delete)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}