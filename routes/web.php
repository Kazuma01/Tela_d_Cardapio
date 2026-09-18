<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardapioController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\KitchenController;

Route::get('/users', [UserController::class, 'index'])->name('users.index');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cardapio', [CardapioController::class, 'index'])->name('cardapio.index');

Route::get('/salas/criar', [RoomController::class, 'create'])->name('rooms.create');
Route::post('/salas', [RoomController::class, 'store'])->name('rooms.store');
Route::get('/salas/entrar', [RoomController::class, 'joinList'])->name('rooms.join.list');
Route::get('/salas/{room}/entrar', [RoomController::class, 'joinForm'])->name('rooms.join.form');
Route::post('/salas/{room}/entrar', [RoomController::class, 'join'])->name('rooms.join');
Route::get('/salas/{room}', [RoomController::class, 'show'])->name('rooms.show');

Route::get('/salas/{room}/cozinha', [KitchenController::class, 'index'])->name('cozinha.index');
Route::post('/salas/{room}/pedidos/{order}/pronto', [KitchenController::class, 'markReady'])->name('cozinha.pronto');

Route::get('/salas/{room}/pedidos/criar', [OrderController::class, 'create'])->name('orders.create');
Route::post('/salas/{room}/pedidos', [OrderController::class, 'store'])->name('orders.store');
Route::get('/salas/{room}/pedidos/{order}/editar', [OrderController::class, 'edit'])->name('orders.edit');
Route::put('/salas/{room}/pedidos/{order}', [OrderController::class, 'update'])->name('orders.update');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
