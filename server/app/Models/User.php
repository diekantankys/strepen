<?php

namespace App\Models;

use App\Notifications\LowBalance;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    // A user can be male, female or other
    public const GENDER_MALE = 0;

    public const GENDER_FEMALE = 1;

    public const GENDER_OTHER = 2;

    // A user can be normal, a manager or an admin
    public const ROLE_NORMAL = 0;

    public const ROLE_MANAGER = 1;

    public const ROLE_ADMIN = 2;

    // A user can select the english and the dutch language
    public const LANGUAGE_ENGLISH = 0;

    public const LANGUAGE_DUTCH = 1;

    // A user can select a light and a dark theme
    public const THEME_LIGHT = 0;

    public const THEME_DARK = 1;

    public const THEME_SYSTEM = 2;

    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
    ];

    protected $casts = [
        'birthday' => 'datetime:Y-m-d',
        'receive_news' => 'boolean',
        'balance' => 'double',
        'active' => 'boolean',
    ];

    protected $attributes = [
        'role' => User::ROLE_NORMAL,
        'language' => User::LANGUAGE_DUTCH,
        'theme' => User::THEME_SYSTEM,
        'receive_news' => true,
        'balance' => 0,
        'active' => true,
    ];

    protected $fillable = [
        'avatar',
        'thanks',
    ];

    // Generate a random avatar name
    public static function generateAvatarName($extension)
    {
        if ($extension == 'jpeg') {
            $extension = 'jpg';
        }
        $avatar = Str::random(32).'.'.$extension;
        if (static::where('avatar', $avatar)->count() > 0) {
            return static::generateAvatarName($extension);
        }

        return $avatar;
    }

    // Generate a random thanks name
    public static function generateThanksName($extension)
    {
        $thanks = Str::random(32).'.'.$extension;
        if (static::where('thanks', $thanks)->count() > 0) {
            return static::generateThanksName($extension);
        }

        return $thanks;
    }

    // Recalculate user balance
    public function recalculateBalance()
    {
        // Refresh relationships
        unset($this->transactions);

        // Recount balance
        $this->balance = 0;

        if ($this->id != 1) {
            // Loop through all transactions and adjust balance
            $transactions = $this->transactions;
            foreach ($transactions as $transaction) {
                if ($transaction->type == Transaction::TYPE_TRANSACTION) {
                    $this->balance -= $transaction->price;
                }
                if ($transaction->type == Transaction::TYPE_DEPOSIT) {
                    $this->balance += $transaction->price;
                }
                if ($transaction->type == Transaction::TYPE_PAYMENT) {
                    $this->balance -= $transaction->price;
                }
            }
        }
    }

    // Get user full name (firstname insertion lastname)
    public function getNameAttribute()
    {
        if ($this->insertion != null) {
            return $this->firstname.' '.$this->insertion.' '.$this->lastname;
        } else {
            return $this->firstname.' '.$this->lastname;
        }
    }

    // Check if user is minor
    public function getMinorAttribute()
    {
        return $this->birthday != null && $this->birthday->diff(new DateTime('now'))->y < Setting::get('minor_age');
    }

    // Check if user is normal
    public function getNormalAttribute()
    {
        return $this->role == User::ROLE_NORMAL;
    }

    // Check if user is manager or admin
    public function getManagerAttribute()
    {
        return $this->role == User::ROLE_MANAGER || $this->role == User::ROLE_ADMIN;
    }

    // Check if user is admin
    public function getAdminAttribute()
    {
        return $this->role == User::ROLE_ADMIN;
    }

    // A user has many posts
    /** @return HasMany<Post> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // A user has many inventories
    /** @return HasMany<Inventory> */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    // A user has many transactions
    /** @return HasMany<Transaction> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // A user has many notifications
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->orderByRaw('case when read_at IS NULL then 0 else 1 end')
            ->orderBy('created_at', 'desc');
    }

    // Search by a query
    public static function search($query, $searchQuery)
    {
        return $query->where(fn ($query) => $query->where('firstname', 'LIKE', '%'.$searchQuery.'%')
            ->orWhere('insertion', 'LIKE', '%'.$searchQuery.'%')
            ->orWhere('lastname', 'LIKE', '%'.$searchQuery.'%')
            ->orWhere('email', 'LIKE', '%'.$searchQuery.'%')
            ->orWhere('created_at', 'LIKE', '%'.$searchQuery.'%'));
    }

    // Get balance chart data
    public function getBalanceChart($startDate, $endDate)
    {
        // Covert start and end date to timestamp
        $firstTransaction = $this->transactions()->orderBy('created_at')->first();
        if ($firstTransaction != null) {
            $startDate = max(strtotime($startDate), $firstTransaction->created_at->getTimestamp());
        } else {
            $startDate = strtotime(date('Y-m-d'));
        }
        $endDate = min(strtotime($endDate), strtotime(date('Y-m-d')));

        // Get the deposits price sum and transactions price sum before start date
        $startDepositsPrice = DB::table('transactions')
            ->where('user_id', $this->id)
            ->whereNull('deleted_at')
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->where('created_at', '<', date('Y-m-d H:i:s', $startDate))
            ->sum('price');

        $startTransactionsPrice = DB::table('transactions')
            ->where('user_id', $this->id)
            ->whereNull('deleted_at')
            ->where(fn ($query) => $query->where('type', Transaction::TYPE_TRANSACTION)
                ->orWhere('type', Transaction::TYPE_PAYMENT))
            ->where('created_at', '<', date('Y-m-d H:i:s', $startDate))
            ->sum('price');

        // Get the rest of the transactions between this time
        $transactions = $this->transactions()->orderBy('created_at')
            ->where('created_at', '>=', date('Y-m-d H:i:s', $startDate))
            ->where('created_at', '<', date('Y-m-d H:i:s', $endDate + 24 * 60 * 60))
            ->get();

        // Loop trough days
        $balance = $startDepositsPrice - $startTransactionsPrice;
        $days = ceil((($endDate + 24 * 60 * 60) - $startDate + 1) / (24 * 60 * 60));
        $balanceData = [];
        $index = 0;
        for ($day = 0; $day < $days; $day++) {
            $dayTime = $startDate + $day * (24 * 60 * 60);
            // Ajust balance by using the transactions of that day
            while (
                $index < $transactions->count() &&
                $transactions[$index]->created_at->getTimestamp() >= $dayTime &&
                $transactions[$index]->created_at->getTimestamp() < $dayTime + (24 * 60 * 60)
            ) {
                if ($transactions[$index]->type == Transaction::TYPE_TRANSACTION) {
                    $balance -= $transactions[$index]->price;
                }
                if ($transactions[$index]->type == Transaction::TYPE_DEPOSIT) {
                    $balance += $transactions[$index]->price;
                }
                if ($transactions[$index]->type == Transaction::TYPE_PAYMENT) {
                    $balance -= $transactions[$index]->price;
                }
                $index++;
            }
            $balanceData[] = [date('Y-m-d', $dayTime), $balance];
        }

        return $balanceData;
    }

    // Check gravatar for avatar
    public function checkGravatarAvatar()
    {
        $headers = implode('\n', get_headers('https://www.gravatar.com/avatar/'.md5($this->email).'?d=404'));
        if (str_contains($headers, '200 OK') && (str_contains($headers, 'Content-Type: image/jpeg') || str_contains($headers, 'Content-Type: image/png'))) {
            if (str_contains($headers, 'Content-Type: image/jpeg')) {
                $this->avatar = static::generateAvatarName('jpg');
            }
            if (str_contains($headers, 'Content-Type: image/png')) {
                $this->avatar = static::generateAvatarName('png');
            }
            file_put_contents(storage_path('app/public/avatars/').$this->avatar, file_get_contents('https://www.gravatar.com/avatar/'.md5($this->email).'?s=512'));
        }
    }

    // Send all users that have a to low balance an low balance notification
    public static function checkBalances()
    {
        $users = User::where('active', true)->where('balance', '<', Setting::get('min_user_balance'))->get();
        foreach ($users as $user) {
            $user->notify(new LowBalance($user));
        }
    }
}
