<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'type' => Transaction::TYPE_TRANSACTION,
            'name' => 'Fake transaction on '.date('Y-m-d H:i:s'),
            'price' => $this->faker->numberBetween(1, 10),
        ];
    }
}
