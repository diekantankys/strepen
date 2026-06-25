<?php

namespace App\Http\Livewire\Transactions;

use App\Http\Livewire\Components\ProductsChooser;
use App\Http\Livewire\Components\UserChooser;
use App\Http\Livewire\Concerns\ManagesChooserValidation;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    use ManagesChooserValidation;

    public $transaction;

    public $selectedProducts = [];

    public $isMinor = false;

    public $isCreated;

    public function rules()
    {
        $rules = [
            'transaction.name' => 'required|min:2|max:48',
            'selectedProducts.*.product_id' => 'required|integer|exists:products,id',
            'selectedProducts.*.amount' => 'required|integer|min:1|max:'.Setting::get('max_stripe_amount'),
        ];
        if (Auth::id() == 1) {
            $rules['transaction.user_id'] = 'required|integer|exists:users,id';
        }

        return $rules;
    }

    public $listeners = ['inputValue'];

    public function mount()
    {
        $this->resetTransactionForm();
    }

    private function resetTransactionForm()
    {
        $this->transaction = new Transaction;
        $this->transaction->name = __('transactions.create.name_default').' '.date('Y-m-d H:i:s');
        $this->selectedProducts = [];
        $this->isMinor = false;
        $this->invalidInputs = [];
        $this->isCreated = false;
    }

    public function inputValue($name, $value)
    {
        if ($name == 'user') {
            $this->setInputInvalid('user', false);
            $this->transaction->user_id = $value;

            $user = User::find($this->transaction->user_id);
            if ($user != null && $user->minor) {
                $this->isMinor = true;
                $this->dispatch('inputProps', 'products', [
                    'minor' => $this->isMinor,
                ])->to(ProductsChooser::class);
            }
            if ($this->isMinor && ($user == null || ! $user->minor)) {
                $this->isMinor = false;
                $this->dispatch('inputProps', 'products', [
                    'minor' => $this->isMinor,
                ])->to(ProductsChooser::class);
            }
        }

        if ($name == 'products') {
            $this->setInputInvalid('products', false);
            $this->selectedProducts = $value;
        }
    }

    public function createTransaction()
    {
        if ($this->isCreated) {
            return;
        }

        // Validate input
        if (Auth::id() == 1) {
            $this->requireInput('user', $this->transaction->user_id);
        }
        $this->requireInput('products', collect($this->selectedProducts)->filter(fn ($selectedProduct) => $selectedProduct['amount'] > 0));
        $this->dispatch('inputValidate', 'user')->to(UserChooser::class);
        $this->dispatch('inputValidate', 'products')->to(ProductsChooser::class);
        $this->validate();

        $selectedProducts = collect($this->selectedProducts)->map(function ($selectedProduct) {
            $product = Product::findOrFail($selectedProduct['product_id']);
            $product->selectedAmount = $selectedProduct['amount'];

            return $product;
        });

        if ($selectedProducts->count() == 0) {
            return;
        }

        if (Auth::id() != 1) {
            $this->transaction->user_id = Auth::id();
        }
        $user = User::find($this->transaction->user_id);
        if ($user->minor) {
            foreach ($selectedProducts as $product) {
                if ($product->alcoholic) {
                    return;
                }
            }
        }

        // Create transaction
        $this->transaction->price = 0;
        foreach ($selectedProducts as $product) {
            $this->transaction->price += $product->price * $product->selectedAmount;
        }
        $this->transaction->type = Transaction::TYPE_TRANSACTION;
        $this->transaction->save();

        // Attach products to transaction and decrement product amount
        foreach ($selectedProducts as $product) {
            $this->transaction->products()->attach($product, [
                'price' => $product->price,
                'amount' => $product->selectedAmount,
            ]);
            $product->amount -= $product->selectedAmount;
            unset($product->selectedAmount);
            $product->save();
        }

        // Recalculate balance of user
        $user->balance -= $this->transaction->price;
        $user->save();

        $this->isCreated = true;
    }

    public function closeCreated()
    {
        $this->dispatch('inputClear', 'user')->to(UserChooser::class);
        $this->dispatch('inputClear', 'products')->to(ProductsChooser::class);
        $this->resetTransactionForm();
    }

    public function render()
    {
        return view('livewire.transactions.create')
            ->layout('layouts.app', ['title' => __('transactions.create.title')]);
    }
}
