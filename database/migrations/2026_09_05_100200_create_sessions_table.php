<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('senha'); // guardada com hash
            $table->foreignId('criado_por')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['aberta', 'encerrada'])->default('aberta');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};