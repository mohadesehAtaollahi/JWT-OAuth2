<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;

class CategoryController extends Controller
{
    /**
     * GET /api/products/categories
     output: [{ slug, name, url }]
     */
    public function index()
    {

        $categories = Category::orderBy('name')->get(['slug', 'name']);

        $data = $categories->map(function ($cat) {
            return [
                'slug' => $cat->slug,
                'name' => $cat->name,
                'url'  => url("/api/products/category/{$cat->slug}"),
            ];
        });

        return response()->json($data);
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
        $category = Category::where('slug', $slug)->firstOrFail();

        $limit  = (int) $request->query('limit', 30);
        $skip   = (int) $request->query('skip', 0);

        $baseQuery = Product::where('category_id', $category->id);

        $total = $baseQuery->count();

        $products = $baseQuery
            ->with(['category', 'images', 'tags', 'reviews'])
            ->skip($skip)
            ->take($limit)
            ->get()
            ->map(function ($p) use ($category) {
                $arr = $p->toArray();
                $arr['category'] = $category->slug;
                unset($arr['category_id']);
                return $arr;
            });

        return response()->json([
            'products' => $products,
            'total'    => $total,
            'skip'     => $skip,
            'limit'    => $limit,
        ]);
    }

}
