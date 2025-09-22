<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'price',
        'discount_percentage',
        'rating',
        'stock',
        'sku',
        'weight',
        'warranty_information',
        'shipping_information',
        'availability_status',
        'return_policy',
        'minimum_order_quantity',
        'category_id',
        'brand_id',
        'barcode',
        'qr_code',
        'thumbnail'
    ];
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function dimensions()
    {
        return $this->hasOne(ProductDimension::class);
    }
}
