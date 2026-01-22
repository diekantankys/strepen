<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use Illuminate\Http\Request;

class ApiInventoriesController extends ApiController
{
    // Api inventories index route
    public function index(Request $request)
    {
        $inventories = $this->getItems(Inventory::class, Inventory::select(), $request)
            ->with(['user', 'products'])
            ->orderBy('created_at', 'DESC')
            ->paginate($this->getLimit($request))->withQueryString();

        return InventoryResource::collection($inventories);
    }

    // Api inventories show route
    public function show(Inventory $inventory)
    {
        $inventory->user;
        $inventory->products;

        return new InventoryResource($inventory);
    }
}
