<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProductImageSearchController extends Controller
{
    public function index(): View
    {
        return view('products.image-search');
    }

    public function catalog(): JsonResponse
    {
        $products = Product::active()->orderBy('code')->orderBy('id')
            ->get(['id', 'code', 'name', 'category', 'image_path', 'updated_at', 'stock_quantity']);
        $catalog = $products->groupBy('code')->map(function ($variants) {
            $first = $variants->first();
            $images = $variants->filter(function ($product) {
                $path = $product->image_path;
                return $path && ! str_contains($path, '..') && ! str_contains($path, ':')
                    && ! str_starts_with($path, '/') && ! str_contains($path, '\\');
            })->unique('image_path')->map(fn ($product) => [
                'url' => asset('storage/' . $product->image_path),
                'version' => $product->updated_at?->format('Y-m-d H:i:s.u') ?? '',
            ])->values();

            return ['id' => $first->id, 'code' => $first->code, 'name' => $first->name,
                'category' => $first->category, 'quantity' => (int) $variants->sum('stock_quantity'),
                'url' => route('products.show', $first), 'images' => $images];
        })->filter(fn ($product) => $product['images']->isNotEmpty())->values();

        return response()->json(['products' => $catalog])
            ->header('Cache-Control', 'private, no-store');
    }
}
