<?php

namespace App\Http\Livewire\Admin\Products;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Item extends Component
{
    use WithFileUploads;

    public $product;

    public $image;

    public $isShowing = false;

    public $startDate;

    public $isEditing = false;

    public $isDeleting = false;

    public $rules = [
        'product.name' => 'required|min:2|max:48',
        'product.price' => 'required|numeric',
        'product.description' => 'nullable',
        'image' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        'product.alcoholic' => 'nullable|boolean',
        'product.active' => 'nullable|boolean',
    ];

    public function mount()
    {
        $firstTransaction = $this->product->transactions()->orderBy('created_at')->first();
        $firstInventory = $this->product->inventories()->orderBy('created_at')->first();
        if ($firstTransaction != null || $firstInventory != null) {
            $oldestItem = $firstTransaction ?? $firstInventory;
            if ($firstTransaction != null && $firstInventory != null) {
                $firstTransaction->created_at->getTimestamp() < $firstInventory->created_at->getTimestamp()
                    ? $firstTransaction
                    : $firstInventory;
            }

            $maxDiff = 365 * 24 * 60 * 60;
            if (time() - $oldestItem->created_at->getTimestamp() < $maxDiff) {
                $this->startDate = $oldestItem->created_at->format('Y-m-d');
            } else {
                $this->startDate = date('Y-m-d', time() - $maxDiff);
            }
        } else {
            $this->startDate = date('Y-m-d');
        }
    }

    public function editProduct()
    {
        $this->validate();

        if ($this->image != null) {
            $imageName = Product::generateImageName($this->image->extension());
            $this->image->storeAs('public/products', $imageName);

            if ($this->product->image != null) {
                Storage::delete('public/products/'.$this->product->image);
            }
            $this->product->image = $imageName;
            $this->image = null;
        }

        $this->isEditing = false;
        $this->product->save();
        $this->emitUp('refresh');
    }

    public function deleteImage()
    {
        if ($this->product->image != null) {
            Storage::delete('public/products/'.$this->product->image);
        }
        $this->product->image = null;
        $this->product->save();
        $this->emitUp('refresh');
    }

    public function deleteProduct()
    {
        $this->isDeleting = false;
        $this->product->delete();
        $this->emitUp('refresh');
    }

    public function render()
    {
        return view('livewire.admin.products.item');
    }
}
