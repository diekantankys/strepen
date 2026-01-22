<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;

class ApiNotificationsController extends Controller
{
    // API notifications read route
    public function read(Notification $notification)
    {
        $notification->read_at = now();
        $notification->save();

        return [
            'message' => 'The notification is successfully read',
        ];
    }
}
