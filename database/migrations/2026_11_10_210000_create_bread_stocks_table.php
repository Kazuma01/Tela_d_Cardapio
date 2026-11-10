<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bread_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('quantidade')->default(0);
            $table->timestamps();
        });

        // Estoque inicial: um único registro global controla o contador de pão
        DB::table('bread_stocks')->insert([
            'quantidade' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bread_stocks');
    }
};
