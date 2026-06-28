<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('receive_news', 'notify_new_posts');
            $table->boolean('notify_low_balance')->default(true)->after('notify_new_posts');
            $table->boolean('notify_new_deposits')->default(true)->after('notify_low_balance');
            $table->boolean('notify_new_transactions')->default(false)->after('notify_new_deposits');
            $table->boolean('notify_by_email')->default(true)->after('notify_new_transactions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_low_balance', 'notify_new_deposits', 'notify_new_transactions', 'notify_by_email']);
            $table->renameColumn('notify_new_posts', 'receive_news');
        });
    }
};
