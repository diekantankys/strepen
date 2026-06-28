<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\InventoryResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Models\FcmToken;
use App\Models\Inventory;
use App\Models\Post;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiUsersController extends ApiController
{
    // Api users index route
    public function index(Request $request)
    {
        $users = $this->getItems(User::class, User::select(), $request)
            ->orderByRaw('active DESC, LOWER(lastname)')
            ->paginate($this->getLimit($request))->withQueryString();
        if (! $request->user()->manager) {
            $users = $users->where('active', true);
        }

        return UserResource::collection($users);
    }

    // Api users show route
    public function show(User $user)
    {
        return new UserResource($user);
    }

    // Api users show notifcations route
    public function showNotifications(Request $request, User $user)
    {
        $notifications = $request->user()->notifications()
            ->paginate($this->getLimit($request))->withQueryString();

        return NotificationResource::collection($notifications);
    }

    // Api users show unread notifcations route
    public function showUnreadNotifications(Request $request, User $user)
    {
        $notifications = $request->user()->unreadNotifications()
            ->paginate($this->getLimit($request))->withQueryString();

        return NotificationResource::collection($notifications);
    }

    // Api users show posts route
    public function showPosts(Request $request, User $user)
    {
        $posts = $this->getItems(Post::class, $user->posts(), $request)
            ->orderBy('created_at', 'DESC')
            ->paginate($this->getLimit($request))->withQueryString();

        return PostResource::collection($posts);
    }

    // Api users show inventories route
    public function showInventories(Request $request, User $user)
    {
        $inventories = $this->getItems(Inventory::class, $user->inventories(), $request)
            ->with('products')
            ->orderBy('created_at', 'DESC')
            ->paginate($this->getLimit($request))->withQueryString();

        return InventoryResource::collection($inventories);
    }

    // Api users show transactions route
    public function showTransactions(Request $request, User $user)
    {
        $transactions = $this->getItems(Transaction::class, $user->transactions(), $request)
            ->with(['user', 'products']) // For backwards compatability
            ->orderBy('created_at', 'DESC')
            ->paginate($this->getLimit($request))->withQueryString();

        return TransactionResource::collection($transactions);
    }

    // Api users check balances route
    public function checkBalances()
    {
        User::checkBalances();

        return [
            'message' => 'User balances are checked',
        ];
    }

    // Api users edit route
    public function edit(Request $request, User $user)
    {
        // Validate input
        $rules = [
            'insertion' => 'nullable|max:16',
            'gender' => [
                'nullable',
                Rule::in(['', 'male', 'female', 'other']),
            ],
            'birthday' => 'nullable|date',
            'phone' => 'nullable|max:255',
            'address' => 'nullable|min:2|max:255',
            'postcode' => 'nullable|min:2|max:32',
            'city' => 'nullable|min:2|max:255',
            'language' => [
                'nullable',
                Rule::in(['en', 'nl']),
            ],
            'theme' => [
                'nullable',
                Rule::in(['light', 'dark', 'system']),
            ],
            'notify_new_posts' => [
                'nullable',
                Rule::in(['true', 'false']),
            ],
            'notify_low_balance' => [
                'nullable',
                Rule::in(['true', 'false']),
            ],
            'notify_new_deposits' => [
                'nullable',
                Rule::in(['true', 'false']),
            ],
            'notify_new_transactions' => [
                'nullable',
                Rule::in(['true', 'false']),
            ],
            'notify_by_email' => [
                'nullable',
                Rule::in(['true', 'false']),
            ],
        ];
        if ($request->has('firstname')) {
            $rules['firstname'] = 'required|min:2|max:48';
        }
        if ($request->has('lastname')) {
            $rules['lastname'] = 'required|min:2|max:48';
        }
        if ($request->has('email')) {
            $rules['email'] = [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->email, 'email'),
            ];
        }
        if ($request->has('avatar') && $request->input('avatar') != 'null') {
            $rules['avatar'] = 'required|image|mimes:jpg,jpeg,png|max:1024';
        }
        if ($request->has('thanks') && $request->input('thanks') != 'null') {
            $rules['thanks'] = 'required|image|mimes:gif|max:2048';
        }
        if ($request->hasAny(['current_password', 'password', 'password_confirmation'])) {
            $rules['current_password'] = 'required|current_password';
            $rules['password'] = 'required|min:6';
            $rules['password_confirmation'] = 'required|same:password';
        }
        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response(['errors' => $validation->errors()], 400);
        }

        // Update details
        if ($request->has('firstname')) {
            $user->firstname = $request->input('firstname');
        }

        if ($request->has('insertion')) {
            $user->insertion = $request->input('insertion');
        }

        if ($request->has('lastname')) {
            $user->lastname = $request->input('lastname');
        }

        if ($request->has('gender')) {
            if ($request->input('gender') == '') {
                $user->gender = null;
            }
            if ($request->input('gender') == 'male') {
                $user->gender = User::GENDER_MALE;
            }
            if ($request->input('gender') == 'female') {
                $user->gender = User::GENDER_FEMALE;
            }
            if ($request->input('gender') == 'other') {
                $user->gender = User::GENDER_OTHER;
            }
        }

        if ($request->has('birthday')) {
            $user->birthday = $request->input('birthday');
        }

        if ($request->has('email')) {
            $user->email = $request->input('email');
        }

        if ($request->has('phone')) {
            $user->phone = $request->input('phone');
        }

        if ($request->has('address')) {
            $user->address = $request->input('address');
        }

        if ($request->has('postcode')) {
            $user->postcode = $request->input('postcode');
        }

        if ($request->has('city')) {
            $user->city = $request->input('city');
        }

        if ($request->has('language')) {
            if ($request->input('language') == 'en') {
                $user->language = User::LANGUAGE_ENGLISH;
            }
            if ($request->input('language') == 'nl') {
                $user->language = User::LANGUAGE_DUTCH;
            }
        }

        if ($request->has('theme')) {
            if ($request->input('theme') == 'light') {
                $user->theme = User::THEME_LIGHT;
            }
            if ($request->input('theme') == 'dark') {
                $user->theme = User::THEME_DARK;
            }
            if ($request->input('theme') == 'system') {
                $user->theme = User::THEME_SYSTEM;
            }
        }

        if ($request->has('notify_new_posts')) {
            $user->notify_new_posts = $request->input('notify_new_posts') == 'true';
        }

        if ($request->has('notify_low_balance')) {
            $user->notify_low_balance = $request->input('notify_low_balance') == 'true';
        }

        if ($request->has('notify_new_deposits')) {
            $user->notify_new_deposits = $request->input('notify_new_deposits') == 'true';
        }

        if ($request->has('notify_new_transactions')) {
            $user->notify_new_transactions = $request->input('notify_new_transactions') == 'true';
        }

        if ($request->has('notify_by_email')) {
            $user->notify_by_email = $request->input('notify_by_email') == 'true';
        }

        // Update avatar
        if ($request->has('avatar')) {
            if ($request->input('avatar') == 'null') {
                // Delete user avatar file from storage
                Storage::delete('public/avatars/'.$user->avatar);

                // Update user that he has no avatar
                $user->avatar = null;
            } else {
                // Save file to avatars folder
                $avatar = $request->file('avatar');
                $avatarName = User::generateAvatarName($avatar->extension());
                $avatar->storeAs('public/avatars', $avatarName);

                // Delete old user avatar
                if ($user->avatar != null) {
                    Storage::delete('public/avatars/'.$user->avatar);
                }

                // Update user that he has an avatar
                $user->avatar = $avatarName;
            }
        }

        // Update thanks
        if ($request->has('thanks')) {
            if ($request->input('thanks') == 'null') {
                // Delete user thanks file from storage
                Storage::delete('public/thanks/'.$user->thanks);

                // Update user that he has no thanks
                $user->thanks = null;
            } else {
                // Save file to thanks folder
                $thanks = $request->file('thanks');
                $thanksName = User::generateThanksName($thanks->extension());
                $thanks->storeAs('public/thanks', $thanksName);

                // Delete old user thanks
                if ($user->thanks != null) {
                    Storage::delete('public/thanks/'.$user->thanks);
                }

                // Update user that he has an thanks
                $user->thanks = $thanksName;
            }
        }

        // Update password
        if ($request->has(['current_password', 'password', 'password_confirmation'])) {
            $user->password = Hash::make($request->input('password'));
        }

        // Save and send response message
        $user->save();

        return [
            'message' => 'All user changes are saved!',
            'user' => new UserResource($user),
        ];
    }

    // Api users store fcm token route
    public function storeFcmToken(Request $request, User $user)
    {
        $validation = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255',
        ]);
        if ($validation->fails()) {
            return response(['errors' => $validation->errors()], 400);
        }

        FcmToken::updateOrCreate(
            ['token' => $request->input('fcm_token')],
            ['user_id' => $user->id],
        );

        return ['message' => 'FCM token registered'];
    }

    // Api users destroy fcm token route
    public function destroyFcmToken(Request $request, User $user)
    {
        $validation = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255',
        ]);
        if ($validation->fails()) {
            return response(['errors' => $validation->errors()], 400);
        }

        $user->fcmTokens()->where('token', $request->input('fcm_token'))->delete();

        return ['message' => 'FCM token removed'];
    }
}
