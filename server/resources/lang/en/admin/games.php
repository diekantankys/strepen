<?php

return [
    'title' => 'Games',

    'index' => [
        'title' => 'Games',
        'header' => 'Games',
        'wanted_description' => 'Manage faces for the Wanted game',
    ],

    'crud' => [
        'header' => 'Wanted',
        'faces' => 'faces',
        'create_face' => 'Add face',
        'name' => 'Name',
        'image' => 'Photo',
        'image_help' => 'JPG or PNG, max 2 MB.',
        'cancel' => 'Cancel',
        'empty' => 'No faces added yet.',

        'name_asc' => 'Name A–Z',
        'name_desc' => 'Name Z–A',
        'created_at_desc' => 'Newest first',
        'created_at_asc' => 'Oldest first',
    ],

    'item' => [
        'delete' => 'Delete',
        'delete_face' => 'Delete face',
        'delete_description' => 'Are you sure you want to delete this face? This action cannot be undone.',
        'cancel' => 'Cancel',
    ],
];
