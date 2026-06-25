<?php

namespace App\Http\Livewire\Admin\Transactions;

use App\Http\Livewire\Components\ProductsChooser;
use App\Http\Livewire\Components\UserChooser;
use App\Http\Livewire\Concerns\ManagesChooserValidation;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Component;

class Item extends Component
{
    use ManagesChooserValidation;

    public $transaction;

    public $oldUserId;

    public $createdAtDate;

    public $createdAtTime;

    public $selectedProducts = [];

    public $isEditing = false;

    public $isDeleting = false;

    public $rules = [
        'transaction.user_id' => 'required|integer|exists:users,id',
        'transaction.name' => 'required|min:2|max:48',
        'createdAtDate' => 'required|date_format:Y-m-d',
        'createdAtTime' => 'required|date_format:H:i:s',
        'selectedProducts.*.product_id' => 'required|integer|exists:products,id',
        'selectedProducts.*.price' => 'required|numeric',
        'selectedProducts.*.amount' => 'required|integer|min:1',
        'transaction.price' => 'required|numeric',
    ];

    public $listeners = ['inputValue'];

    public function mount()
    {
        $this->oldUserId = $this->transaction->user_id;

        $this->createdAtDate = $this->transaction->created_at->format('Y-m-d');
        $this->createdAtTime = $this->transaction->created_at->format('H:i:s');

        foreach ($this->transaction->products as $product) {
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
            $this->setInputInvalid('item_user', false);
            $this->transaction->user_id = $value;
        }

        if ($name == 'item_products') {
            $this->setInputInvalid('item_products', false);
            $this->selectedProducts = $value;
        }
    }

    public function editTransaction()
    {
        // Validate same input
        $this->requireInput('item_user', $this->transaction->user_id);
        $this->dispatch('inputValidate', 'item_user')->to(UserChooser::class);
        if ($this->transaction->type == Transaction::TYPE_TRANSACTION) {
            $this->requireInput('item_products', collect($this->selectedProducts)->filter(fn ($selectedProduct) => $selectedProduct['amount'] > 0));
            $this->dispatch('inputValidate', 'item_products')->to(ProductsChooser::class);
        }
        $this->validateOnly('transaction.user_id');
        $this->validateOnly('transaction.name');
        $this->validateOnly('createdAtDate');
        $this->validateOnly('createdAtTime');

        if ($this->transaction->type == Transaction::TYPE_TRANSACTION) {
            // Validate input
            $this->validateOnly('selectedProducts.*.product_id');
            $this->validateOnly('selectedProducts.*.amount');

            $selectedProducts = collect($this->selectedProducts)->map(function ($selectedProduct) {
                $product = Product::findOrFail($selectedProduct['product_id']);
                $product->selectedPrice = $selectedProduct['price'];
                $product->selectedAmount = $selectedProduct['amount'];

                return $product;
            });

            if ($selectedProducts->count() == 0) {
                return;
            }

            // Edit transaction
            $this->transaction->price = 0;
            foreach ($selectedProducts as $product) {
                $this->transaction->price += $product->selectedPrice * $product->selectedAmount;
            }

            // Detach and attach products to transaction
            $this->transaction->products()->detach();
            foreach ($selectedProducts as $product) {
                $this->transaction->products()->attach($product, [
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
        }

        if ($this->transaction->type == Transaction::TYPE_DEPOSIT || $this->transaction->type == Transaction::TYPE_PAYMENT) {
            // Validate input
            $this->validateOnly('transaction.price');
        }

        $this->transaction->created_at = $this->createdAtDate.' '.$this->createdAtTime;
        $this->transaction->save();

        // Recalculate old user balance
        if ($this->oldUserId != $this->transaction->user_id) {
            $user = User::find($this->oldUserId);
            $user->recalculateBalance();
            $user->save();
            $this->oldUserId = $this->transaction->user_id;
        }

        // Recalculate user balance
        $user = User::find($this->transaction->user_id);
        $user->recalculateBalance();
        $user->save();

        $this->isEditing = false;
        $this->dispatch('refresh')->to(Crud::class);
    }

    public function deleteTransaction()
    {
        $this->isDeleting = false;

        // Delete and recalculate user balance
        $this->transaction->delete();

        $user = $this->transaction->user;
        $user->recalculateBalance();
        $user->save();

        // Recalculate amounts of all inventory products
        foreach ($this->transaction->products as $product) {
            $product->recalculateAmount();
            $product->save();
        }

        $this->dispatch('refresh')->to(Crud::class);
    }

    public function render()
    {
        unset($this->transaction->user);
        unset($this->transaction->products);

        return view('livewire.admin.transactions.item');
    }
}
