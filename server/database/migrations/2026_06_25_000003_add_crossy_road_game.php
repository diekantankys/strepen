<?php

use App\Models\Game;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Game::create(['name' => 'Kratje Hop', 'slug' => 'crossy-road']);
    }

    public function down(): void
    {
        Game::where('slug', 'crossy-road')->delete();
    }
};
