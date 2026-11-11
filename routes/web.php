<?php

use App\Http\Controllers\CardapioController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cardapio', [CardapioController::class, 'index'])->name('cardapio.index');

/*
|--------------------------------------------------------------------------
| Área autenticada (salas, pedidos e cozinha)
|--------------------------------------------------------------------------
| Todas as rotas abaixo usam Auth::id() e o pivot room_user. Sem o
| middleware "auth", um visitante causaria erro 500 e, pior, contornaria
| o controle de participação. O throttle no join evita força bruta na
| senha da sala (mínimo de 4 caracteres).
*/
Route::middleware('auth')->group(function () {
    // Salas
    Route::get('/salas/criar', [RoomController::class, 'create'])->name('rooms.create');
    Route::post('/salas', [RoomController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('rooms.store');
    Route::get('/salas/entrar', [RoomController::class, 'joinList'])->name('rooms.join.list');
    Route::get('/salas/{room}/entrar', [RoomController::class, 'joinForm'])->name('rooms.join.form');
    Route::post('/salas/{room}/entrar', [RoomController::class, 'join'])
        ->middleware('throttle:10,1')
        ->name('rooms.join');
    Route::get('/salas/{room}', [RoomController::class, 'show'])->name('rooms.show');
    Route::get('/salas/{room}/encerrar', [RoomController::class, 'encerrarForm'])->name('rooms.encerrar.form');
    Route::patch('/salas/{room}/encerrar', [RoomController::class, 'encerrar'])->name('rooms.encerrar');

    // Cozinha
    Route::get('/salas/{room}/cozinha', [KitchenController::class, 'index'])->name('cozinha.index');
    Route::post('/salas/{room}/pedidos/{order}/pronto', [KitchenController::class, 'markReady'])->name('cozinha.pronto');

    // Estoque e relatório
    Route::patch('/salas/{room}/estoque-pao', [RoomController::class, 'atualizarEstoque'])->name('rooms.estoque');
    Route::get('/salas/{room}/relatorio-vendas', [RoomController::class, 'relatorioVendas'])->name('rooms.relatorio');

    // Pedidos
    Route::delete('/salas/{room}/pedidos/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::patch('/salas/{room}/pedidos/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('/salas/{room}/pedidos/criar', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/salas/{room}/pedidos', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/salas/{room}/pedidos/{order}/editar', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/salas/{room}/pedidos/{order}', [OrderController::class, 'update'])->name('orders.update');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
