<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ProductsAmounts extends Component
{
    public $products;

    public $totalPrice;

    public function __construct($products, $totalPrice)
    {
        $this->products = $products;
        $this->totalPrice = $totalPrice;

        // If product pivot prices are null and current prices are correct use them
        $firstProduct = $this->products->first();
        if ($firstProduct != null && $firstProduct->pivot->price == null) {
            $realTotalPrice = $this->products->reduce(
                fn ($price, $product) => $price + $product->price * $product->pivot->amount
            );
            if (abs($this->totalPrice - $realTotalPrice) < 0.01) {
                foreach ($this->products as $product) {
                    $product->pivot->price = $product->price;
                }
            }
        }
    }

    public function render()
    {
        return view('components.products-amounts');
    }
}
