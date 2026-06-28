<?php

namespace App\Http\Resources;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        $gender = null;
        if ($this->gender == User::GENDER_MALE) {
            $gender = 'male';
        }
        if ($this->gender == User::GENDER_FEMALE) {
            $gender = 'female';
        }
        if ($this->gender == User::GENDER_OTHER) {
            $gender = 'other';
        }

        $role = 'unknown';
        if ($this->role == User::ROLE_NORMAL) {
            $role = 'normal';
        }
        if ($this->role == User::ROLE_MANAGER) {
            $role = 'manager';
        }
        if ($this->role == User::ROLE_ADMIN) {
            $role = 'admin';
        }

        $language = 'unknown';
        if ($this->language == User::LANGUAGE_ENGLISH) {
            $language = 'en';
        }
        if ($this->language == User::LANGUAGE_DUTCH) {
            $language = 'nl';
        }

        $theme = 'unknown';
        if ($this->theme == User::THEME_LIGHT) {
            $theme = 'light';
        }
        if ($this->theme == User::THEME_DARK) {
            $theme = 'dark';
        }
        if ($this->theme == User::THEME_SYSTEM) {
            $theme = 'system';
        }

        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'insertion' => $this->insertion,
            'lastname' => $this->lastname,
            'avatar' => asset('/storage/avatars/'.($this->avatar ?? Setting::get('default_user_avatar'))),
            'thanks' => asset('/storage/thanks/'.($this->thanks ?? Setting::get('default_user_thanks'))),
            $this->mergeWhen($request->user()->manager || $this->id == $request->user()->id, [
                'gender' => $gender,
                'birthday' => $this->birthday != null ? $this->birthday->format('Y-m-d') : null,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'postcode' => $this->postcode,
                'city' => $this->city,
                'role' => $role,
                'lanuage' => $language,
                'theme' => $theme,
                'notify_new_posts' => $this->notify_new_posts,
                'notify_low_balance' => $this->notify_low_balance,
                'notify_new_deposits' => $this->notify_new_deposits,
                'notify_new_transactions' => $this->notify_new_transactions,
                'notify_by_email' => $this->notify_by_email,
                'balance' => $this->balance,
                'minor' => $this->minor,
                'created_at' => $this->created_at,
            ]),
            $this->mergeWhen($request->user()->manager, [
                'active' => $this->active,
                'updated_at' => $this->updated_at,
            ]),
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            'inventories' => InventoryResource::collection($this->whenLoaded('inventories')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
