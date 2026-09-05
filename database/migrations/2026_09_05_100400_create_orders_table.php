<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criado_por')->constrained('users')->cascadeOnDelete();
            $table->string('identificacao', 100)->nullable(); // "Mesa 3", "João - retirada", etc.
            $table->text('observacao')->nullable();
            $table->enum('status', ['pendente', 'em_preparo', 'pronto', 'entregue'])->default('pendente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};