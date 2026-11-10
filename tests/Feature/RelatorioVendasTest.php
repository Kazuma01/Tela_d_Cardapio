<?php

use App\Models\Order;
use App\Models\User;

test('relatório de vendas gera um PDF', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto', 6.0);
    $xtudo = criarProduto('X-Tudo', 15.0);

    $order = Order::create([
        'room_id' => $room->id,
        'criado_por' => $garcom->id,
        'status' => 'entregue',
    ]);
    $order->items()->createMany([
        ['product_id' => $misto->id, 'quantidade' => 2, 'preco_unitario' => 6.0],
        ['product_id' => $xtudo->id, 'quantidade' => 1, 'preco_unitario' => 15.0],
    ]);

    $response = $this->actingAs($garcom)->get("/salas/{$room->id}/relatorio-vendas");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    // O PDF nunca pode ser servido do cache do navegador — sempre dados atuais
    // (o Symfony pode reordenar as diretivas e adicionar "private", então validamos por conteúdo)
    $cache = $response->headers->get('Cache-Control') ?? '';
    expect($cache)->toContain('no-store')->toContain('no-cache')->toContain('must-revalidate');
});

test('quem não é garçom da sala não vê o relatório', function () {
    [, $room] = criarSalaComGarcom();
    $intruso = User::factory()->create();

    $response = $this->actingAs($intruso)->get("/salas/{$room->id}/relatorio-vendas");

    $response->assertForbidden();
});
