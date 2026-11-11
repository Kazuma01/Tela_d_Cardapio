<?php

use App\Models\BreadStock;
use App\Models\Order;

test('criar pedido deduz os pães do estoque', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto');
    $tradicional = criarProduto('Tradicional');

    $estoqueAntes = BreadStock::atual()->quantidade;

    $response = $this->actingAs($garcom)->post("/salas/{$room->id}/pedidos", [
        'identificacao' => 'Mesa 1',
        'itens' => [
            $misto->id => ['quantidade' => 2, 'observacao' => ''],
            $tradicional->id => ['quantidade' => 3, 'observacao' => ''],
        ],
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    expect(BreadStock::atual()->quantidade)->toBe($estoqueAntes - 5);
});

test('estoque de pão zero bloqueia a criação de pedidos', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto');

    BreadStock::atual()->definir(0);

    $response = $this->actingAs($garcom)->post("/salas/{$room->id}/pedidos", [
        'itens' => [$misto->id => ['quantidade' => 1, 'observacao' => '']],
    ]);

    $response->assertSessionHasErrors('itens');
    expect($room->orders()->count())->toBe(0);
});

test('pedido maior que o estoque é bloqueado com aviso', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto');

    BreadStock::atual()->definir(4);

    $response = $this->actingAs($garcom)->post("/salas/{$room->id}/pedidos", [
        'itens' => [$misto->id => ['quantidade' => 8, 'observacao' => '']],
    ]);

    $response->assertSessionHasErrors('itens');
    expect(BreadStock::atual()->quantidade)->toBe(4);
});

test('editar pedido consome a diferença quando aumenta', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto');

    $order = Order::create([
        'room_id' => $room->id,
        'criado_por' => $garcom->id,
        'status' => 'pendente',
    ]);
    $order->items()->create([
        'product_id' => $misto->id,
        'quantidade' => 2,
        'preco_unitario' => $misto->preco,
    ]);

    $estoqueAntes = BreadStock::atual()->quantidade;

    $response = $this->actingAs($garcom)->put("/salas/{$room->id}/pedidos/{$order->id}", [
        'itens' => [$misto->id => ['quantidade' => 5, 'observacao' => '']],
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    expect(BreadStock::atual()->quantidade)->toBe($estoqueAntes - 3);
});

test('editar pedido devolve pães quando diminui', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto');

    $order = Order::create([
        'room_id' => $room->id,
        'criado_por' => $garcom->id,
        'status' => 'pendente',
    ]);
    $order->items()->create([
        'product_id' => $misto->id,
        'quantidade' => 5,
        'preco_unitario' => $misto->preco,
    ]);

    $estoqueAntes = BreadStock::atual()->quantidade;

    $response = $this->actingAs($garcom)->put("/salas/{$room->id}/pedidos/{$order->id}", [
        'itens' => [$misto->id => ['quantidade' => 2, 'observacao' => '']],
    ]);

    $response->assertRedirect(route('rooms.show', $room));
    expect(BreadStock::atual()->quantidade)->toBe($estoqueAntes + 3);
});

test('excluir pedido devolve os pães ao estoque', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto');

    $order = Order::create([
        'room_id' => $room->id,
        'criado_por' => $garcom->id,
        'status' => 'pendente',
    ]);
    $order->items()->create([
        'product_id' => $misto->id,
        'quantidade' => 4,
        'preco_unitario' => $misto->preco,
    ]);

    $estoqueAntes = BreadStock::atual()->quantidade;

    $response = $this->actingAs($garcom)->delete("/salas/{$room->id}/pedidos/{$order->id}");

    $response->assertRedirect(route('rooms.show', $room));
    expect(BreadStock::atual()->quantidade)->toBe($estoqueAntes + 4);
});

test('garçom ajusta o estoque de pão manualmente', function () {
    [$garcom, $room] = criarSalaComGarcom();

    $response = $this->actingAs($garcom)->patch("/salas/{$room->id}/estoque-pao", [
        'quantidade' => 42,
    ]);

    $response->assertRedirect();
    expect(BreadStock::atual()->quantidade)->toBe(42);
});

test('quem não é garçom da sala não ajusta o estoque', function () {
    [, $room] = criarSalaComGarcom();
    $intruso = App\Models\User::factory()->create();

    $response = $this->actingAs($intruso)->patch("/salas/{$room->id}/estoque-pao", [
        'quantidade' => 999,
    ]);

    $response->assertForbidden();
    expect(BreadStock::atual()->quantidade)->toBe(100);
});

test('baixa de pão é atômica e nunca deixa o estoque negativo', function () {
    BreadStock::atual()->definir(3);

    expect(BreadStock::atual()->consumirSeDisponivel(2))->toBeTrue();
    expect(BreadStock::atual()->quantidade)->toBe(1);

    // Simula uma segunda requisição chegando depois da primeira: já não há 2 pães.
    expect(BreadStock::atual()->consumirSeDisponivel(2))->toBeFalse();
    expect(BreadStock::atual()->quantidade)->toBe(1);
});

test('dois pedidos disputando o último pão: só o primeiro é criado', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto');
    BreadStock::atual()->definir(2);

    $this->actingAs($garcom)->post("/salas/{$room->id}/pedidos", [
        'itens' => [$misto->id => ['quantidade' => 2, 'observacao' => '']],
    ])->assertRedirect(route('rooms.show', $room));

    $this->actingAs($garcom)->post("/salas/{$room->id}/pedidos", [
        'itens' => [$misto->id => ['quantidade' => 1, 'observacao' => '']],
    ])->assertSessionHasErrors('itens');

    expect($room->orders()->count())->toBe(1);
    expect(BreadStock::atual()->quantidade)->toBe(0);
});

test('editar pedido sem pão suficiente não altera o pedido nem o estoque', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto');
    BreadStock::atual()->definir(2);

    $order = Order::create([
        'room_id' => $room->id,
        'criado_por' => $garcom->id,
        'status' => 'pendente',
    ]);
    $order->items()->create([
        'product_id' => $misto->id,
        'quantidade' => 1,
        'preco_unitario' => $misto->preco,
    ]);

    $this->actingAs($garcom)->put("/salas/{$room->id}/pedidos/{$order->id}", [
        'itens' => [$misto->id => ['quantidade' => 4, 'observacao' => '']],
    ])->assertSessionHasErrors('itens');

    expect((int) $order->items()->sum('quantidade'))->toBe(1);
    expect(BreadStock::atual()->quantidade)->toBe(2);
});
