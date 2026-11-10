<?php

use App\Models\User;

test('quem criou a sala vê a tela de encerramento', function () {
    [$garcom, $room] = criarSalaComGarcom();

    $response = $this->actingAs($garcom)->get("/salas/{$room->id}/encerrar");

    $response->assertOk();
});

test('quem não criou a sala não pode encerrá-la', function () {
    [$garcom, $room] = criarSalaComGarcom();

    // outro garçom da mesma sala
    $irmao = User::factory()->create();
    $room->participantes()->attach($irmao->id, ['papel' => 'garcom']);

    $this->actingAs($irmao)->get("/salas/{$room->id}/encerrar")->assertForbidden();
    $this->actingAs($irmao)->patch("/salas/{$room->id}/encerrar")->assertForbidden();

    expect($room->fresh()->status)->toBe('aberta');
});

test('encerrar muda o status e volta pra lista de salas', function () {
    [$garcom, $room] = criarSalaComGarcom();

    $response = $this->actingAs($garcom)->patch("/salas/{$room->id}/encerrar");

    $response->assertRedirect(route('rooms.join.list'));
    expect($room->fresh()->status)->toBe('encerrada');
});

test('sala encerrada não aceita novos pedidos', function () {
    [$garcom, $room] = criarSalaComGarcom();
    $misto = criarProduto('Misto');

    $room->update(['status' => 'encerrada']);

    $this->actingAs($garcom)->get("/salas/{$room->id}/pedidos/criar")->assertForbidden();

    $response = $this->actingAs($garcom)->post("/salas/{$room->id}/pedidos", [
        'itens' => [$misto->id => ['quantidade' => 1, 'observacao' => '']],
    ]);

    $response->assertForbidden();
    expect($room->orders()->count())->toBe(0);
});

test('sala encerrada não aparece na lista de salas abertas', function () {
    [$garcom, $room] = criarSalaComGarcom();

    $room->update(['status' => 'encerrada']);

    $this->actingAs($garcom)->get('/salas/entrar')
        ->assertOk()
        ->assertDontSee($room->nome);
});
