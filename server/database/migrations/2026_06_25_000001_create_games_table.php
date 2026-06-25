<?php

use App\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Game::create(['name' => 'Flappy Flügel', 'slug' => 'flappy-bird']);
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
