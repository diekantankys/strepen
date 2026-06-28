<?php

return [
    // Notifications page
    'title' => 'Notifications',
    'header' => 'Your notifications history',
    'empty' => 'No notifications',
    'unread' => 'Unread',

    'new_deposit_header' => 'New deposit',
    'new_deposit_start' => 'You have received a new deposit of',
    'new_deposit_end' => 'on',

    'new_post_header' => 'New post',
    'new_post_text' => 'A new post called \':post.title\' can been seen on the home page on :post.created_at',

    'low_balance_header' => 'Balance to low',
    'low_balance_start' => 'Your balance of',
    'low_balance_end' => 'is to low deposit some money to clear your debt',

    // Mail / FCM
    'greeting' => 'Dear :name,',
    'salutation' => 'Regards, the board',

    'new_post_mail_subject' => ':title - A new post on the Strepen System',
    'new_post_mail_line1' => 'A new post has been published on the Strepen System:',
    'new_post_fcm_title' => 'New post',

    'new_deposit_mail_subject' => 'New deposit on the Strepen System',
    'new_deposit_mail_line1' => 'A deposit of :currency :amount has been added to your account!',
    'new_deposit_mail_line2' => 'Your balance is now :currency :balance.',
    'new_deposit_fcm_title' => 'New deposit',
    'new_deposit_fcm_body' => ':currency :amount deposited',

    'low_balance_mail_subject' => 'Balance too low on the Strepen System',
    'low_balance_mail_line1' => 'After entering your last purchased products at the club, it turned out that your balance is below :currency :min_balance. Your balance is currently :currency :balance.',
    'low_balance_mail_line2' => 'According to the board this is too little! Please top up your balance as soon as possible by transferring money to account :iban in the name of :holder.',
    'low_balance_mail_line3' => 'If you have any questions or think something is wrong, please reply to this email.',
    'low_balance_fcm_title' => 'Balance too low',
    'low_balance_fcm_body' => 'Your balance is :currency :balance',
];
