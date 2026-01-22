<?php

return [
    // Transactions create livewire component
    'create.title' => 'Stripe',
    'create.header_kiosk' => 'Select your name and stripe products',
    'create.header_no_kiosk' => 'Stripe products on your personal account',
    'create.create_transaction' => 'Stripe',
    'create.name' => 'Name',
    'create.name_default' => 'Transaction on',

    // Transactions history livewire component
    'history.title' => 'History',
    'history.header' => 'Your personal stripe history',
    'history.created_at_desc' => 'At created at (new - old)',
    'history.created_at_asc' => 'At created at (old - new)',
    'history.name_asc' => 'Name (A - Z)',
    'history.name_desc' => 'Name (Z - A)',
    'history.price_desc' => 'Price (high - low)',
    'history.price_asc' => 'Price (low - high)',
    'history.transactions' => 'transactions',
    'history.empty' => 'No stripes found!',
    'history.transaction_on' => 'Transaction on :transaction.created_at',
    'history.deposit_on' => 'Deposit on :transaction.created_at',
    'history.payment_on' => 'Payment on :transaction.created_at',
    'history.amount' => 'Amount',
];
