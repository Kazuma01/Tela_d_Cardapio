<?php

use App\Models\User;

test('visitante consegue ver o cardápio sem estar logado', function () {
    $this->get('/cardapio')->assertOk();
});

test('usuário logado também consegue ver o cardápio', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/cardapio')->assertOk();
});
