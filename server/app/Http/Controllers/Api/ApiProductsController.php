<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ApiProductsController extends ApiController
{
    // Api products index route
    public function index(Request $request)
    {
        $products = $this->getItems(Product::class, Product::select(), $request)
            ->orderByRaw('active DESC, LOWER(name)');
        if (! $request->user()->manager) {
            $products = $products->where('active', true);
        }
        $products = $products->paginate($this->getLimit($request))->withQueryString();

        return ProductResource::collection($products);
    }

    // Api products show route
    public function show(Product $product)
    {
        return new ProductResource($product);
    }
}
