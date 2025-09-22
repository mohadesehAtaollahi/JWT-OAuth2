<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * GET /api/products/categories
     output: [{ slug, name, url }]
     */
    public function index()
    {
        $categories = Category::orderBy('name')->get(['slug', 'name']);
        return CategoryResource::collection($categories);
    }

    /**
     * GET /api/products/category-list
     * output: ["beauty","fragrances",...]
     */
    public function list()
    {
        $slugs = Category::orderBy('name')->pluck('slug');
        return response()->json($slugs);
    }

    /**
     * GET /api/products/category/{slug}?limit=&skip=&select=
     * output: { products: [...], total, skip, limit }
     */
    public function productsByCategory(Request $request, string $slug)
    {
        $validator = Validator::make($request->all(),
            ['limit' => 'integer|min:1|max:100',
            'skip' => 'integer|min:0',]
        );
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $limit  = (int) $request->query('limit', 30);
        $skip   = (int) $request->query('skip', 0);

        $category = Category::where('slug', $slug)->firstOrFail();
        $baseQuery = Product::where('category_id', $category->id);
        $total = $baseQuery->count();

        $products = $baseQuery
            ->with(['category','brand', 'images', 'tags', 'reviews', 'dimensions'])
            ->skip($skip)
            ->take($limit)
            ->get();
        return response()->json([
            'products' => ProductResource::collection($products),
            'total' => $total,
            'skip' => $skip,
            'limit' => $limit,
        ]);
    }

}
