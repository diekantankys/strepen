<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChangeDefaultProductImage extends Component
{
    use WithFileUploads;

    public $image;

    public $isChanged = false;

    public $isDeleted = false;

    public $rules = [
        'image' => 'required|image|mimes:jpg,jpeg,png|max:1024',
    ];

    public function changeImage()
    {
        $this->validate();

        // Save file to image folder
        $imageName = Product::generateImageName($this->image->extension());
        $this->image->storeAs('public/products', $imageName);

        // Delete old global image when not default
        if (Setting::get('default_product_image') != 'default.png') {
            Storage::delete('public/products/'.Setting::get('default_product_image'));
        }

        // Update global image
        Setting::set('default_product_image', $imageName);
        $this->image = null;
        $this->isChanged = true;
    }

    public function deleteImage()
    {
        // Delete global image
        if (Setting::get('default_product_image') != 'default.png') {
            Storage::delete('public/products/'.Setting::get('default_product_image'));
        }

        // Update global image to default one
        Setting::set('default_product_image', 'default.png');
        $this->isDeleted = true;
    }

    public function render()
    {
        return view('livewire.admin.settings.change-default-product-image');
    }
}
