<?php

use App\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('name');
            $table->string('image_path');
            $table->timestamps();
        });

        Game::create(['name' => 'Gezocht', 'slug' => 'wanted']);
    }

    public function down(): void
    {
        Schema::dropIfExists('game_assets');
        Game::where('slug', 'wanted')->delete();
    }
};
