<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\Transaction;
use Illuminate\Console\Command;

class FixPivotPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix-pivot-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix all product pivot prices';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $oldProductPrices = $this->fixTransactions();
        $this->fixInventories($oldProductPrices);

        return 0;
    }

    private function fixTransactions()
    {
        // Fix all transaction product price with one product
        echo "Fixing transaction product prices with single product...\n";
        $oldProductPrices = [];
        Transaction::with('products')->where('type', Transaction::TYPE_TRANSACTION)->chunk(100, function ($transactions) use (&$oldProductPrices) {
            foreach ($transactions as $transaction) {
                echo '.';
                if ($transaction->products->count() === 1) {
                    $product = $transaction->products->first();
                    if ($product->pivot->price == null) {
                        $product->pivot->price = $transaction->price / $product->pivot->amount;
                        $product->pivot->save();
                    }

                    if (! isset($oldProductPrices[$product->id])) {
                        $oldProductPrices[$product->id] = [];
                    }
                    $lastProductPrice = end($oldProductPrices[$product->id]);
                    if ($lastProductPrice === false || $lastProductPrice['price'] != $product->pivot->price) {
                        $oldProductPrices[$product->id][] = [
                            'price' => $product->pivot->price,
                            'time' => $transaction->created_at->getTimestamp(),
                        ];
                    }
                }
            }
        });

        // Fix all transaction product price with multiple products
        echo "\nFixing transaction product prices with multiple products...\n";
        Transaction::with('products')->where('type', Transaction::TYPE_TRANSACTION)->chunk(100, function ($transactions) use ($oldProductPrices) {
            foreach ($transactions as $transaction) {
                echo '.';
                if ($transaction->products->count() > 1) {
                    if ($transaction->products->first()->pivot->price == null) {
                        foreach ($transaction->products as $product) {
                            // Find the closest old price for this product
                            if (isset($oldProductPrices[$product->id])) {
                                $closestPrice = null;
                                $closestTimeDiff = null;
                                foreach ($oldProductPrices[$product->id] as $oldProductPrice) {
                                    $timeDiff = abs($transaction->created_at->getTimestamp() - $oldProductPrice['time']);
                                    if ($closestTimeDiff === null || $timeDiff < $closestTimeDiff) {
                                        $closestTimeDiff = $timeDiff;
                                        $closestPrice = $oldProductPrice['price'];
                                    }
                                }
                                if ($closestPrice !== null) {
                                    $product->pivot->price = $closestPrice;
                                }
                            }
                        }

                        $newTotalPrice = 0;
                        foreach ($transaction->products as $product) {
                            if ($product->pivot->price != null) {
                                $newTotalPrice += $product->pivot->price * $product->pivot->amount;
                            }
                        }

                        if (abs($newTotalPrice - $transaction->price) < 0.01) {
                            foreach ($transaction->products as $product) {
                                $product->pivot->save();
                            }
                        }
                    }
                }
            }
        });

        // Print stats
        $count = Transaction::where('type', Transaction::TYPE_TRANSACTION)->whereHas('products', function ($query) {
            $query->whereNull('transaction_product.price');
        })->count();
        echo "\nDone! Transactions with missing product prices left: $count\n";

        return $oldProductPrices;
    }

    private function fixInventories($oldProductPrices)
    {
        // Fix all inventory product price with multiple products
        echo "\nFixing inventory product prices with multiple products...\n";
        Inventory::with('products')->chunk(100, function ($inventories) use ($oldProductPrices) {
            foreach ($inventories as $inventory) {
                echo '.';
                if ($inventory->products->first()->pivot->price == null) {
                    foreach ($inventory->products as $product) {
                        // Find the closest old price for this product
                        if (isset($oldProductPrices[$product->id])) {
                            $closestPrice = null;
                            $closestTimeDiff = null;
                            foreach ($oldProductPrices[$product->id] as $oldProductPrice) {
                                $timeDiff = abs($inventory->created_at->getTimestamp() - $oldProductPrice['time']);
                                if ($closestTimeDiff === null || $timeDiff < $closestTimeDiff) {
                                    $closestTimeDiff = $timeDiff;
                                    $closestPrice = $oldProductPrice['price'];
                                }
                            }
                            if ($closestPrice !== null) {
                                $product->pivot->price = $closestPrice;
                            }
                        }
                    }

                    $newTotalPrice = 0;
                    foreach ($inventory->products as $product) {
                        if ($product->pivot->price != null) {
                            $newTotalPrice += $product->pivot->price * $product->pivot->amount;
                        }
                    }

                    if (abs($newTotalPrice - $inventory->price) < 0.01) {
                        foreach ($inventory->products as $product) {
                            $product->pivot->save();
                        }
                    }
                }
            }
        });

        // Print stats
        $count = Inventory::whereHas('products', function ($query) {
            $query->whereNull('inventory_product.price');
        })->count();
        echo "\nDone! inventories with missing product prices left: $count\n";
    }
}
