<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Livewire\Admin\ApiKeys\Crud as ApiKeysCrud;
use App\Http\Livewire\Admin\Games\Index as AdminGamesIndex;
use App\Http\Livewire\Admin\Inventories\Crud as InventoriesCrud;
use App\Http\Livewire\Admin\PostComments\Crud as PostCommentsCrud;
use App\Http\Livewire\Admin\Posts\Crud as PostsCrud;
use App\Http\Livewire\Admin\Products\Crud as ProductsCrud;
use App\Http\Livewire\Admin\Transactions\Crud as TransactionsCrud;
use App\Http\Livewire\Admin\Users\Crud;
use App\Http\Livewire\Auth\ForgotPassword;
use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Auth\ResetPassword;
use App\Http\Livewire\Balance;
use App\Http\Livewire\Games\CrossyRoad;
use App\Http\Livewire\Games\FlappyBird;
use App\Http\Livewire\Games\Index as GamesIndex;
use App\Http\Livewire\Games\Wanted;
use App\Http\Livewire\Home;
use App\Http\Livewire\Leaderboards;
use App\Http\Livewire\Notifications;
use App\Http\Livewire\Posts\Show;
use App\Http\Livewire\Transactions\Create;
use App\Http\Livewire\Transactions\History;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

Route::view('/apps', 'apps')->name('apps');

Route::view('/release-notes', 'release-notes')->name('release-notes');

Route::view('/account-deletion', 'account-deletion')->name('account-deletion');

// Auth routes
Route::middleware('auth')->group(function () {
    Route::get('/games', GamesIndex::class)->name('games.index');
    Route::get('/games/flappy-bird', FlappyBird::class)->name('games.flappy-bird');
    Route::get('/games/crossy-road', CrossyRoad::class)->name('games.crossy-road');
    Route::get('/games/wanted', Wanted::class)->name('games.wanted');

    Route::get('/posts/{post}', Show::class)->name('posts.show');

    Route::get('/stripe', Create::class)->name('transactions.create');

    Route::middleware('leaderboards')->group(function () {
        Route::get('/leaderboards', Leaderboards::class)->name('leaderboards');
    });

    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // No kiosk routes
    Route::middleware('nokiosk')->group(function () {
        Route::get('/transactions', History::class)->name('transactions.history');

        Route::get('/notifications', Notifications::class)->name('notifications');

        Route::get('/balance', Balance::class)->name('balance');

        Route::view('/settings', 'settings')->name('settings');
    });

    // Manager routes
    Route::middleware('manager')->group(function () {
        Route::view('/admin', 'admin.home')->name('admin.home');

        Route::get('/admin/users', Crud::class)->name('admin.users.crud');

        Route::get('/admin/posts', PostsCrud::class)->name('admin.posts.crud');

        Route::get('/admin/post-comments', PostCommentsCrud::class)->name('admin.post_comments.crud');

        Route::get('/admin/products', ProductsCrud::class)->name('admin.products.crud');

        Route::get('/admin/inventories', InventoriesCrud::class)->name('admin.inventories.crud');

        Route::get('/admin/transactions', TransactionsCrud::class)->name('admin.transactions.crud');

        Route::view('/admin/games', 'admin.games')->name('admin.games.index');
        Route::get('/admin/games/wanted', AdminGamesIndex::class)->name('admin.games.wanted');
    });

    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::view('/admin/settings', 'admin.settings')->name('admin.settings');

        Route::get('/admin/api_keys', ApiKeysCrud::class)->name('admin.api_keys.crud');
    });
});

// Can kiosk routes
Route::middleware('cankiosk')->group(function () {
    Route::get('/admin/kiosk', [AdminController::class, 'kiosk'])->name('admin.kiosk');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/auth/login', Login::class)->name('auth.login');

    Route::get('/auth/forgot-password', ForgotPassword::class)->name('auth.forgot_password');

    Route::get('/auth/reset-password/{token}', ResetPassword::class)->name('password.reset');
});
