<?php

use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Tempo real (Reverb + Echo)
|--------------------------------------------------------------------------
| Guarda de regressão do bug V0.8.0: o pusher-js não tem um transporte
| chamado "wss". O transporte é sempre "ws" e o forceTLS decide se a
| conexão usa ws:// ou wss://. Com enabledTransports: ['wss'] a conexão
| falha em silêncio e o tempo real nunca funciona.
*/

test('echo usa o transporte ws do pusher (o TLS vem do forceTLS)', function () {
    $echo = file_get_contents(base_path('resources/js/echo.js'));

    expect($echo)->toContain("enabledTransports: ['ws']");
    expect($echo)->not->toContain("enabledTransports: ['wss']");
    expect($echo)->toContain('forceTLS: useTls');
});

test('o certificado público da CA está disponível para download', function () {
    expect(File::exists(public_path('cardapio-ca-raiz.crt')))->toBeTrue();

    $conteudo = File::get(public_path('cardapio-ca-raiz.crt'));

    expect($conteudo)->toContain('BEGIN CERTIFICATE');
    expect($conteudo)->not->toContain('PRIVATE KEY');
});
