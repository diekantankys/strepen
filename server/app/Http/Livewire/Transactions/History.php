<?php

namespace App\Http\Livewire\Transactions;

use App\Http\Livewire\PaginationComponent;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class History extends PaginationComponent
{
    public $type;

    public $product_id;

    public $productIdTemp;

    public function __construct()
    {
        parent::__construct();
        $this->queryString['type'] = ['except' => ''];
        $this->queryString[] = 'product_id';
        $this->listeners[] = 'inputValue';
    }

    public function mount()
    {
        if (
            $this->sort_by != 'created_at' && $this->sort_by != 'name' && $this->sort_by != 'name_desc' &&
            $this->sort_by != 'price_desc' && $this->sort_by != 'price'
        ) {
            $this->sort_by = null;
        }

        if ($this->type != 'transaction' && $this->type != 'deposit' && $this->type != 'payment') {
            $this->type = null;
        }

        if (Product::where('id', $this->product_id)->where('active', true)->count() == 0) {
            $this->product_id = null;
        }
    }

    public function inputValue($name, $value)
    {
        if ($name == 'product_filter') {
            $this->productIdTemp = $value;
        }
    }

    public function search()
    {
        if ($this->type != 'transaction' && $this->type != 'deposit' && $this->type != 'payment') {
            $this->type = null;
        }

        $this->product_id = $this->productIdTemp;
        $this->resetPage();
    }

    public function render()
    {
        $transactions = Transaction::search(Auth::user()->transactions(), $this->query);
        if ($this->type != null) {
            if ($this->type == 'transaction') {
                $type = Transaction::TYPE_TRANSACTION;
            }
            if ($this->type == 'deposit') {
                $type = Transaction::TYPE_DEPOSIT;
            }
            if ($this->type == 'payment') {
                $type = Transaction::TYPE_PAYMENT;
            }
            $transactions = $transactions->where('type', $type);
        }
        if ($this->product_id != null) {
            $transactions = $transactions->whereHas('products', function ($query) {
                return $query->where('product_id', $this->product_id);
            });
        }

        if ($this->sort_by == null) {
            $transactions = $transactions->orderBy('created_at', 'DESC');
        }
        if ($this->sort_by == 'created_at') {
            $transactions = $transactions->orderBy('created_at');
        }
        if ($this->sort_by == 'name') {
            $transactions = $transactions->orderByRaw('LOWER(name)');
        }
        if ($this->sort_by == 'name_desc') {
            $transactions = $transactions->orderByRaw('LOWER(name) DESC');
        }
        if ($this->sort_by == 'price_desc') {
            $transactions = $transactions->orderBy('price', 'DESC');
        }
        if ($this->sort_by == 'price') {
            $transactions = $transactions->orderBy('price');
        }

        return view('livewire.transactions.history', [
            'transactions' => $transactions->paginate(Setting::get('pagination_rows') * 3)->withQueryString(),
        ])->layout('layouts.app', ['title' => __('transactions.history.title')]);
    }
}
