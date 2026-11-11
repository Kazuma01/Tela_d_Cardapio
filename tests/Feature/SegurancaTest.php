<?php

use App\Models\Order;
use App\Models\Room;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Testes de segurança (regressão)
|--------------------------------------------------------------------------
| Cobrem as falhas encontradas na auditoria V0.7.2:
|  - IDOR: pedido de uma sala sendo manipulado por garçom de outra sala
|  - Painel da sala acessível por quem não é participante
|  - Rota pública /users vazando dados
|  - Rotas de sala exigindo autenticação
*/

function criarPedidoNaSala(Room $room, User $criador): Order
{
    $produto = criarProduto('Lanche Seguro', 10.0);

    $order = Order::create([
        'room_id' => $room->id,
        'criado_por' => $criador->id,
        'identificacao' => 'Mesa 1',
        'status' => 'pendente',
    ]);

    $order->items()->create([
        'product_id' => $produto->id,
        'quantidade' => 1,
        'preco_unitario' => 10.0,
    ]);

    return $order;
}

function criarCozinhaNaSala(Room $room): User
{
    $cozinha = User::factory()->create();
    $room->participantes()->attach($cozinha->id, ['papel' => 'cozinha']);

    return $cozinha;
}

test('garçom de uma sala não abre o formulário de pedido de outra sala', function () {
    [$garcomA, $roomA] = criarSalaComGarcom();
    [$garcomB, $roomB] = criarSalaComGarcom();
    $pedidoB = criarPedidoNaSala($roomB, $garcomB);

    $this->actingAs($garcomA)
        ->get("/salas/{$roomA->id}/pedidos/{$pedidoB->id}/editar")
        ->assertNotFound();
});

test('garçom de uma sala não altera pedido de outra sala', function () {
    [$garcomA, $roomA] = criarSalaComGarcom();
    [$garcomB, $roomB] = criarSalaComGarcom();
    $pedidoB = criarPedidoNaSala($roomB, $garcomB);

    $this->actingAs($garcomA)
        ->put("/salas/{$roomA->id}/pedidos/{$pedidoB->id}", [
            'itens' => [],
        ])
        ->assertNotFound();

    expect($pedidoB->fresh()->identificacao)->toBe('Mesa 1');
});

test('garçom de uma sala não altera o status de pedido de outra sala', function () {
    [$garcomA, $roomA] = criarSalaComGarcom();
    [$garcomB, $roomB] = criarSalaComGarcom();
    $pedidoB = criarPedidoNaSala($roomB, $garcomB);

    $this->actingAs($garcomA)
        ->patch("/salas/{$roomA->id}/pedidos/{$pedidoB->id}/status", ['status' => 'entregue'])
        ->assertNotFound();

    expect($pedidoB->fresh()->status)->toBe('pendente');
});

test('garçom de uma sala não exclui pedido de outra sala', function () {
    [$garcomA, $roomA] = criarSalaComGarcom();
    [$garcomB, $roomB] = criarSalaComGarcom();
    $pedidoB = criarPedidoNaSala($roomB, $garcomB);

    $this->actingAs($garcomA)
        ->delete("/salas/{$roomA->id}/pedidos/{$pedidoB->id}")
        ->assertNotFound();

    expect(Order::find($pedidoB->id))->not->toBeNull();
});

test('cozinha de uma sala não marca pedido de outra sala como pronto', function () {
    [, $roomA] = criarSalaComGarcom();
    [$garcomB, $roomB] = criarSalaComGarcom();
    $cozinhaA = criarCozinhaNaSala($roomA);
    $pedidoB = criarPedidoNaSala($roomB, $garcomB);

    $this->actingAs($cozinhaA)
        ->post("/salas/{$roomA->id}/pedidos/{$pedidoB->id}/pronto")
        ->assertNotFound();

    expect($pedidoB->fresh()->status)->toBe('pendente');
});

test('quem não participa da sala não vê o painel da sala', function () {
    [, $room] = criarSalaComGarcom();
    $intruso = User::factory()->create();

    $this->actingAs($intruso)
        ->get("/salas/{$room->id}")
        ->assertRedirect(route('rooms.join.form', $room));
});

test('visitante não autenticado é mandado para o login ao acessar uma sala', function () {
    [, $room] = criarSalaComGarcom();

    $this->get("/salas/{$room->id}")->assertRedirect(route('login'));
});

test('participante continua vendo o painel e editando o próprio pedido', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $pedido = criarPedidoNaSala($room, $garcom);

    $this->actingAs($garcom)->get("/salas/{$room->id}")->assertOk();
    $this->actingAs($garcom)->get("/salas/{$room->id}/pedidos/{$pedido->id}/editar")->assertOk();
});

test('a rota pública /users não existe mais', function () {
    $this->get('/users')->assertNotFound();
});
