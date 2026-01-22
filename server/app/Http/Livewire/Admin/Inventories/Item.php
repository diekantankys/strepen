<?php

namespace App\Http\Livewire\Admin\Inventories;

use App\Models\Product;
use Livewire\Component;

class Item extends Component
{
    public $inventory;

    public $createdAtDate;

    public $createdAtTime;

    public $selectedProducts = [];

    public $isEditing = false;

    public $isDeleting = false;

    public $rules = [
        'inventory.user_id' => 'required|integer|exists:users,id',
        'inventory.name' => 'required|min:2|max:48',
        'createdAtDate' => 'required|date_format:Y-m-d',
        'createdAtTime' => 'required|date_format:H:i:s',
        'selectedProducts.*.product_id' => 'required|integer|exists:products,id',
        'selectedProducts.*.price' => 'required|numeric',
        'selectedProducts.*.amount' => 'required|integer|min:1',
    ];

    public $listeners = ['inputValue'];

    public function mount()
    {
        $this->createdAtDate = $this->inventory->created_at->format('Y-m-d');
        $this->createdAtTime = $this->inventory->created_at->format('H:i:s');

        foreach ($this->inventory->products as $product) {
            $selectedProduct = [];
            $selectedProduct['product_id'] = $product->id;
            $selectedProduct['price'] = $product->pivot->price;
            $selectedProduct['amount'] = $product->pivot->amount;
            $this->selectedProducts[] = $selectedProduct;
        }
    }

    public function inputValue($name, $value)
    {
        if ($name == 'item_user') {
            $this->inventory->user_id = $value;
        }

        if ($name == 'item_products') {
            $this->selectedProducts = $value;
        }
    }

    public function editInventory()
    {
        // Validate input
        $this->emit('inputValidate', 'item_user');
        $this->emit('inputValidate', 'item_products');
        $this->validate();

        $selectedProducts = collect($this->selectedProducts)->map(function ($selectedProduct) {
            $product = Product::find($selectedProduct['product_id']);
            $product->selectedPrice = $selectedProduct['price'];
            $product->selectedAmount = $selectedProduct['amount'];

            return $product;
        });

        if ($selectedProducts->count() == 0) {
            return;
        }

        // Edit inventory
        $this->inventory->price = 0;
        foreach ($selectedProducts as $product) {
            $this->inventory->price += $product->selectedPrice * $product->selectedAmount;
        }
        $this->inventory->created_at = $this->createdAtDate.' '.$this->createdAtTime;
        $this->inventory->save();

        // Detach and attach products to inventory
        $this->inventory->products()->detach();
        foreach ($selectedProducts as $product) {
            $this->inventory->products()->attach($product, [
                'price' => $product->selectedPrice,
                'amount' => $product->selectedAmount,
            ]);
        }

        // Recalculate amounts of all products (Very slow)
        Product::chunk(50, function ($products) {
            foreach ($products as $product) {
                $product->recalculateAmount();
                $product->save();
            }
        });

        $this->isEditing = false;
        $this->emitUp('refresh');
    }

    public function deleteInventory()
    {
        $this->isDeleting = false;
        $this->inventory->delete();

        // Recalculate amounts of all inventory products
        foreach ($this->inventory->products as $product) {
            $product->recalculateAmount();
            $product->save();
        }

        $this->emitUp('refresh');
    }

    public function render()
    {
        unset($this->inventory->user);
        unset($this->inventory->products);

        return view('livewire.admin.inventories.item');
    }
}
