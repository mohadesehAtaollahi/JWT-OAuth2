<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Review;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory()
            ->count(20)
            ->has(Review::factory()->count(5), 'reviews')
            ->create()
            ->each(function ($product) {
                $product->images()->createMany([
                    ['url' => fake()->imageUrl()],
                    ['url' => fake()->imageUrl()],
                ]);

                $tags = Tag::factory()->count(3)->create();
                $product->tags()->sync($tags->pluck('id'));
            });
    }
}
