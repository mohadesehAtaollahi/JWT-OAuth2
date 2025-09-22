<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;
use App\Models\Tag;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Review;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:100',
            'skip' => 'integer|min:0',
            'select' => 'string',
            'sortBy' => 'string|in:id,title,price,created_at',
            'order' => 'string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $limit = (int) $request->get('limit', 30);
        $skip  = (int) $request->get('skip', 0);
        $sortBy = $request->get('sortBy', 'id');
        $order = strtolower($request->get('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $select = $request->get('select');
        $query = Product::query();
        if ($select) {
            $columns = array_map('trim', explode(',', $select));
            if (!in_array('id', $columns)) {
                array_unshift($columns, 'id', 'title');
            }
            $query->select($columns);
            $total = $query->count();
            $products = $query->skip($skip)->take($limit)->orderBy($sortBy, $order)->get();
            return response()->json([
                'products' => $products,
                'total' => $total,
                'skip' => $skip,
                'limit' => $limit,
            ]);
        } else {
            $query->with(['category', 'images', 'tags', 'reviews', 'brand', 'dimensions']);
        }

        $total = $query->count();
        $products = $query->skip($skip)->take($limit)->orderBy($sortBy, $order)->get();

        return response()->json([
            'products' => ProductResource::collection($products),
            'total' => $total,
            'skip' => $skip,
            'limit' => $limit,
        ]);

    }

    /**
     * search in product's title, description, tags and category
     * */

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string',
            'limit' => 'integer|min:1|max:100',
            'skip' => 'integer|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $q = $request->query('q');

        $limit = (int) $request->get('limit', 30);
        $skip  = (int) $request->get('skip', 0);

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


        return response()->json([
            'products' => $products,
            'total' => $total,
            'skip' => $skip,
            'limit' => $limit,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required',
            'brand_id' => 'required',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric|min:0',

            // dimensions as JSON
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'dimensions.depth' => 'nullable|numeric|min:0',


            // thumbnail & images
            'thumbnail' => 'nullable|url',
            'images' => 'nullable|array',
            'images.*' => 'nullable|url',

            // tags
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:100'
        ]);
        if ($data->fails()) {
            return response()->json(['error' => $data->errors()], 422);
        }
        $validated = $data->validated();
        $productData = collect($validated)->except(['images', 'tags', 'dimensions'])->toArray();
        $product = Product::create($productData);
        //store images
        if (!empty($validated['images'])) {
            foreach ($validated['images'] as $imageUrl) {
                $product->images()->create(['url' => $imageUrl]);
            }
        }
        // To store or create tags
        if (!empty($validated['tags'])) {
            $tagIds = [];
            foreach ($validated['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $product->tags()->sync($tagIds);
        }
        if (!empty($validated['dimensions'])) {
            $product->dimensions()->create($validated['dimensions']);
        }
        return response()->json($product->load(['category','brand', 'images', 'tags', 'dimensions']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with(['category','brand','images','tags','reviews', 'dimensions'])
            ->findOrFail($id);
        return response()->json([
            'product' => new ProductResource($product),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = Validator::make($request->all(),[
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'brand_id' => 'sometimes|required|exists:brands,id',
            'price' => 'sometimes|required|numeric|min:0',
            'discountPercentage' => 'nullable|numeric|min:0|max:100',
            'rating' => 'nullable|numeric|min:0|max:5',
            'stock' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',

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
        $validated = $data->validated();
        $productData = collect($validated)->except(['tags','images','dimensions'])->toArray();
        $product->update($productData);

        if (isset($validated['dimensions'])) {
            if ($product->dimension) {
                $product->dimension()->update($validated['dimensions']);
            } else {
                $product->dimension()->create($validated['dimensions']);
            }
        }

        if (!empty($validated['images'])) {
            $product->images()->delete();
            foreach ($validated['images'] as $url) {
                $product->images()->create(['url' => $url]);
            }
        }

        if (!empty($validated['tags'])) {
            $tagIds = [];
            foreach ($validated['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $product->tags()->sync($tagIds);
        }

        return response()->json(new ProductResource($product->load(['category','brand','dimensions','images','tags','reviews'])));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->tags()->detach();
        $product->delete();

        return response()->json([
            'id' => $product->id,
            'title' => $product->title,
            'isDeleted' => true,
            'deletedOn'  => now()->toISOString(),
        ]);

    }

}
