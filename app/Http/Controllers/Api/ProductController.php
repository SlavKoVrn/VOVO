<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource with filters.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'price_from' => 'nullable|numeric|min:0',
            'price_to' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'in_stock' => 'nullable|boolean',
            'rating_from' => 'nullable|numeric|min:0|max:5',
            'sort' => 'nullable|in:price_asc,price_desc,rating_desc,newest',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Валидация диапазона цен
        if (isset($validated['price_from'], $validated['price_to']) && $validated['price_from'] > $validated['price_to']) {
            return response()->json(['message' => 'price_from cannot be greater than price_to'], 400);
        }

        $query = Product::query()
            ->with('category')
            ->when($validated['q'] ?? null, fn($q, $search) => $q->whereFullText('name', $search))
            //->when($validated['q'] ?? null, fn($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($validated['price_from'] ?? null, fn($q, $val) => $q->where('price', '>=', $val))
            ->when($validated['price_to'] ?? null, fn($q, $val) => $q->where('price', '<=', $val))
            ->when($validated['category_id'] ?? null, fn($q, $val) => $q->where('category_id', $val))
            ->when($request->filled('in_stock'), fn($q) => $q->where('in_stock', $request->boolean('in_stock')))
            ->when($validated['rating_from'] ?? null, fn($q, $val) => $q->where('rating', '>=', $val));

        // Сортировка
        $sort = $validated['sort'] ?? 'newest';
        $sortMap = [
            'price_asc' => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            'rating_desc' => ['rating', 'desc'],
            'newest' => ['created_at', 'desc'],
        ];
        [$sortField, $sortDir] = $sortMap[$sort];
        $query->orderBy($sortField, $sortDir);

        // Подгрузка категории если запрошено
        if ($request->boolean('with_category')) {
            $query->with('category');
        }

        $perPage = $validated['per_page'] ?? 20;
        $products = $query->paginate($perPage);
        /*
        $h=fopen(dirname(__FILE__).'/sql.txt','w');
        fwrite($h,print_r($query->toSQL(),true));
        fclose($h);
        */
        return new ProductCollection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'in_stock' => 'boolean',
            'rating' => 'nullable|numeric|min:0|max:5',
        ]);

        $product = Product::create($validated);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // Автоматическая загрузка категории
        $product->load('category');

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'in_stock' => 'sometimes|boolean',
            'rating' => 'nullable|numeric|min:0|max:5',
        ]);

        $product->update($validated);

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
