<?php

namespace App\Http\Resources;

use App\Models\Setting;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => asset('/storage/products/'.($this->image ?? Setting::get('default_product_image'))),
            'price' => $this->price,
            'alcoholic' => $this->alcoholic,
            'created_at' => $this->created_at,
            $this->mergeWhen($request->user()->manager, [
                'inventory_amount' => $this->amount,
                'active' => $this->active,
                'updated_at' => $this->updated_at,
            ]),
            'inventories' => InventoryResource::collection($this->whenLoaded('inventories')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'transactions_count' => $this->transactions()->count(),
        ];
        if ($this->relationLoaded('pivot')) {
            $data['amount'] = $this->pivot->amount;
        }

        return $data;
    }
}
