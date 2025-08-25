<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\support\Str;

use App\Models\Product;
use App\Models\Tag;
use App\Models\ProductImage;
use App\Models\Category;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 30);
        $skip = $request->query('skip', 0);
        $select = $request->query('select');

        if ($select) {
            $columns = array_map('trim', explode(',', $select));
            if (!in_array('id', $columns)) {
                array_unshift($columns, 'id');
            }

            $query = Product::select($columns);
        } else {
            $query = Product::with(['category', 'images', 'tags', 'reviews']);
        }

        $sortBy = $request->query('sortBy', 'id');
        $order  = strtolower($request->query('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedColumns = ['id', 'title', 'price', 'stock', 'rating'];
        if (!in_array($sortBy, $allowedColumns)) {
            $sortBy = 'id';
        }
        $query->orderBy($sortBy, $order);

        $total = $query->count();
        $products = $query->skip($skip)->take($limit)->get();

        return response()->json([
            'products' => $products,
            'total' => $total,
            'skip' => (int)$skip,
            'limit' => (int)$limit,
        ]);
    }

    /**
     * search in product's title, description, tags and category
     * */

    public function search(Request $request)
    {
        $q = $request->query('q');
        if (!$q) {
            return response()->json([
                'products' => [],
                'total' => 0,
                'skip' => 0,
                'limit' => 0
            ]);
        }

        $limit = $request->query('limit', 30);
        $skip = $request->query('skip', 0);

        $query = Product::with('category:id,name')
        ->where(function ($builder) use ($q) {
            $builder->where('title', 'LIKE', "%$q%")
                ->orWhere('description', 'LIKE', "%$q%")
                ->orWhereHas('tags', function ($tagQuery) use ($q) {
                    $tagQuery->where('name', 'LIKE', "%$q%");
                })
                ->orWhereHas('category', function ($catQuery) use ($q) {
                    $catQuery->where('name', 'LIKE', "%$q%");
                });
        });

        $total = $query->count();

        $products = $query
            ->skip($skip)
            ->take($limit)
            ->get(['id', 'title', 'category_id']);


        $products->each(function ($product) {
            $product->category = $product->category ? $product->category->name : null;
            unset($product->category_id);
        });

        return response()->json([
            'products' => $products,
            'total' => $total,
            'skip' => (int) $skip,
            'limit' => (int) $limit,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'rating' => 'nullable|numeric|min:0|max:5',
            'stock' => 'required|integer|min:0',
            'brand' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric|min:0',

            // dimensions as JSON
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'dimensions.depth' => 'nullable|numeric|min:0',

            // extra info
            'warranty_information' => 'nullable|string|max:500',
            'shipping_information' => 'nullable|string|max:500',
            'availability_status' => 'nullable|string|max:100',
            'return_policy' => 'nullable|string|max:500',
            'minimum_order_quantity' => 'nullable|integer|min:1',

            // meta fields directly in product table
            'barcode' => 'nullable|string|max:100',
            'qr_code' => 'nullable|string',

            // thumbnail & images
            'thumbnail' => 'nullable|url',
            'images' => 'nullable|array',
            'images.*' => 'nullable|url',

            // tags
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:100'
        ]);
        $category = Category::firstOrCreate(['name' => Str::title($data['category'])],
            ['slug' => Str::slug($data['category'])]);

        $productData = collect($data)->except(['category','images', 'tags'])->toArray();
        $productData['category_id'] = $category->id;
        $product = Product::create($productData);
        // To store images
        if (!empty($data['images'])) {
            foreach ($data['images'] as $imageUrl) {
                $product->images()->create(['url' => $imageUrl]);
            }
        }
        // To store or create tags
        if (!empty($data['tags'])) {
            $tagIds = [];
            foreach ($data['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $product->tags()->sync($tagIds);
        }
        return response()->json($product->load(['category', 'images', 'tags']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with(['category','images','tags','reviews'])
            ->findOrFail($id);
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'price' => 'sometimes|required|numeric|min:0',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'rating' => 'nullable|numeric|min:0|max:5',
            'stock' => 'nullable|integer|min:0',
            'brand' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric|min:0',

            // dimensions as JSON object fields
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'dimensions.depth' => 'nullable|numeric|min:0',

            'warrantyInformation' => 'nullable|string',
            'shippingInformation' => 'nullable|string',
            'availabilityStatus' => 'nullable|string|max:100',
            'returnPolicy' => 'nullable|string',
            'minimumOrderQuantity' => 'nullable|integer|min:1',

            // meta fields directly in product table
            'barcode' => 'nullable|string|max:100',
            'qr_code' => 'nullable|string',

            // thumbnail & images
            'thumbnail' => 'nullable|url',
            'images' => 'nullable|array',
            'images.*' => 'nullable|url',

            // tags
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:100',
        ]);

        $product->update($data);

        // handle tags separately if provided
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $product->tags()->sync($tagIds);
        }

        // handle images separately if provided
        if ($request->has('images')) {
            foreach ($request->images as $url) {
                $product->images()->create(['url' => $url]);
            }
        }

        return response()->json($product->load(['category', 'images', 'tags', 'reviews']));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'id' => $product->id,
            'title' => $product->title,
            'isDeleted' => true,
            'deletedOn' => now()->toISOString(),
        ]);
    }


    // Custom Methods
}
