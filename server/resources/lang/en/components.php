<?php

return [
    // Search Header blade component
    'search_header.query' => 'Search for :item.name...',
    'search_header.search' => 'Search',

    // Notifications livewire component
    'notifications.empty' => 'No unread notifications',

    'notifications.new_deposit_header' => 'New deposit',
    'notifications.new_deposit_start' => 'You have received a new deposit of',
    'notifications.new_deposit_end' => 'on',

    'notifications.new_post_header' => 'New post',
    'notifications.new_post_text' => 'A new post called \':post.title\' can been seen on the home page on :post.created_at',

    'notifications.low_balance_header' => 'Balance to low',
    'notifications.low_balance_start' => 'Your balance of',
    'notifications.low_balance_end' => 'is to low deposit some money to clear your debt',

    'notifications.new_transaction_header' => 'New transaction',
    'notifications.new_transaction_start' => 'A transaction of',
    'notifications.new_transaction_end' => 'was made on',

    // User Chooser livewire component
    'user_chooser.user' => 'User',
    'user_chooser.search_user' => 'Search an user...',
    'user_chooser.search_by_user' => 'Search by user...',
    'user_chooser.empty' => 'No users found!',
    'user_chooser.empty_error' => 'You must select an user',
    'user_chooser.anonymous' => 'Anonymous',

    // Product Chooser livewire component
    'product_chooser.product' => 'Product',
    'product_chooser.search_product' => 'Search an product...',
    'product_chooser.search_by_product' => 'Search by product...',
    'product_chooser.empty' => 'No products found!',
    'product_chooser.empty_error' => 'You must select an product',

    // Products Chooser livewire component
    'products_chooser.products' => 'Products',
    'products_chooser.products_help' => 'You don\'t have added any products jet!',
    'products_chooser.search_product' => 'Search a product...',
    'products_chooser.add_product' => 'Add product',
    'products_chooser.empty' => 'No products found!',
    'products_chooser.minor' => 'There are less products available because you are a minor',
    'products_chooser.price' => 'Price',
    'products_chooser.amount' => 'Amount',
    'products_chooser.empty_error' => 'You must select at least one product',

    // Transaction Type Chooser blade component
    'transaction_type_chooser_all' => 'All transactions',
    'transaction_type_chooser_transaction' => 'Transactions only',
    'transaction_type_chooser_deposit' => 'Deposits only',
    'transaction_type_chooser_payment' => 'Payments only',

    // Transaction Created Modal blade component
    'transaction_created_modal_header' => 'Your transaction has been saved',
    'transaction_created_modal_thanks' => ':user.firstname, thanks!',
    'transaction_created_modal_new_balance' => 'Your new balance',
    'transaction_created_modal_close' => 'Close',
];
