<?php

return [
    // Notifications page
    'title' => 'Notificaties',
    'header' => 'Jouw notificaties geschiedenis',
    'empty' => 'Geen notificaties',
    'unread' => 'Ongelezen',

    'new_deposit_header' => 'Nieuwe storting',
    'new_deposit_start' => 'U heeft een nieuwe storting van ontvangen',
    'new_deposit_end' => 'op',

    'new_post_header' => 'Nieuw nieuws bericht',
    'new_post_text' => 'Een nieuw bericht genaamd \':post.title\' is te vinden op de home pagina van :post.created_at',

    'low_balance_header' => 'Balans te laag',
    'low_balance_start' => 'Jouw balans van',
    'low_balance_end' => 'is te laag stort wat geld om uw schuld te vereffen',

    // Mail / FCM
    'greeting' => 'Beste :name,',
    'salutation' => 'Groetjes, het stambestuur',

    'new_post_mail_subject' => ':title - Een nieuw nieuws bericht op het Strepen Systeem',
    'new_post_mail_line1' => 'Er is een nieuw nieuws bericht op het Strepen Systeem geplaatst:',
    'new_post_fcm_title' => 'Nieuw nieuws bericht',

    'new_deposit_mail_subject' => 'Nieuwe storting op het Strepen Systeem',
    'new_deposit_mail_line1' => 'Er is een storting van :currency :amount op uw account gezet!',
    'new_deposit_mail_line2' => 'Uw balans is op dit moment nu :currency :balance.',
    'new_deposit_fcm_title' => 'Nieuwe storting',
    'new_deposit_fcm_body' => ':currency :amount gestort',

    'low_balance_mail_subject' => 'Te lage krediet op het Strepen Systeem',
    'low_balance_mail_line1' => 'Na het invoeren van uw laatst gekochte producten op de stam is gebleken dat uw krediet lager dan :currency :min_balance is. Uw balans is op dit moment nu :currency :balance.',
    'low_balance_mail_line2' => 'Dit is volgens het stambestuur te weinig! We willen u dan ook vragen om zo snel mogelijk te verhogen! Dit kan door geld over te maken naar rekening :iban o.v.v. :holder.',
    'low_balance_mail_line3' => 'Mocht u nog vragen hebben of denk u dat er iets niet klopt beantwoord dan dit mailtje.',
    'low_balance_fcm_title' => 'Te laag krediet',
    'low_balance_fcm_body' => 'Uw balans is :currency :balance',

    'new_transaction_header' => 'Nieuwe transactie',
    'new_transaction_start' => 'Er is een transactie gedaan van',
    'new_transaction_end' => 'op',

    'new_transaction_mail_subject' => 'Nieuwe transactie op het Strepen Systeem',
    'new_transaction_mail_line1' => 'Er is een transactie van :currency :amount van uw account afgeschreven.',
    'new_transaction_mail_line2' => 'Uw balans is op dit moment nu :currency :balance.',
    'new_transaction_fcm_title' => 'Nieuwe transactie',
    'new_transaction_fcm_body' => ':currency :amount afgeschreven',
];
