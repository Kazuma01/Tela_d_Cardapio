<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Auditoria e encerramento remoto de sessões
|--------------------------------------------------------------------------
| Cobre os requisitos de segurança da seção 16:
|  - registrar login, falha de login, 403 e ações administrativas;
|  - nunca gravar senha/token/segredo;
|  - permitir encerrar todas as sessões do usuário.
*/

test('login bem-sucedido é registrado na auditoria', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'event' => 'auth.login',
    ]);
});

test('login com senha errada é registrado sem gravar a senha', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'senha-errada',
    ])->assertSessionHasErrors('email');

    $log = AuditLog::where('event', 'auth.login_failed')->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->metadata['email'])->toBe(strtolower($user->email));
    expect(json_encode($log->metadata))->not->toContain('senha-errada');
});

test('tentativa de acesso proibido (403) é registrada', function () {
    [$dono, $room] = criarSalaComGarcom();
    $intruso = User::factory()->create();

    $this->actingAs($intruso)
        ->get("/salas/{$room->id}/encerrar")
        ->assertForbidden();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $intruso->id,
        'event' => 'security.forbidden',
    ]);
});

test('ajuste de estoque registra valor anterior e novo', function () {
    [$garcom, $room] = criarSalaComGarcom();

    $this->actingAs($garcom)->patch("/salas/{$room->id}/estoque-pao", [
        'quantidade' => 42,
    ])->assertRedirect();

    $log = AuditLog::where('event', 'stock.updated')->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($garcom->id);
    expect($log->metadata['para'])->toBe(42);
});

test('auditoria remove campos sensíveis dos metadados', function () {
    AuditLogger::log('teste.sensivel', 'Teste', [
        'password' => 'segredo',
        'token' => 'abc123',
        'nested' => ['senha' => '123456', 'visivel' => 'ok'],
    ]);

    $log = AuditLog::where('event', 'teste.sensivel')->latest('id')->first();

    expect($log->metadata['password'])->toBe('[removido]');
    expect($log->metadata['token'])->toBe('[removido]');
    expect($log->metadata['nested']['senha'])->toBe('[removido]');
    expect($log->metadata['nested']['visivel'])->toBe('ok');
    expect(json_encode($log->metadata))->not->toContain('segredo');
    expect(json_encode($log->metadata))->not->toContain('abc123');
});

test('sair de todos os dispositivos apaga as sessões e invalida o remember token', function () {
    $user = User::factory()->create(['remember_token' => 'token-antigo']);

    foreach (['sessao-1', 'sessao-2'] as $id) {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'teste',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
    }

    $this->actingAs($user)
        ->post('/logout-all')
        ->assertRedirect(route('login'));

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
    expect($user->fresh()->remember_token)->not->toBe('token-antigo');
    $this->assertGuest();
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'event' => 'auth.logout_all',
    ]);
});

test('auditoria antiga é removida pela limpeza automática', function () {
    $velho = AuditLog::create(['event' => 'teste.velho']);
    $velho->forceFill(['created_at' => now()->subDays(91)])->save();

    $novo = AuditLog::create(['event' => 'teste.novo']);

    $this->artisan('model:prune', ['--model' => [AuditLog::class]])
        ->assertSuccessful();

    expect(AuditLog::whereKey($velho->id)->exists())->toBeFalse();
    expect(AuditLog::whereKey($novo->id)->exists())->toBeTrue();
});

test('reverb não aceita qualquer origem', function () {
    $origins = config('reverb.apps.apps.0.allowed_origins');

    expect($origins)->not->toContain('*');
    expect($origins)->toContain('cardapio.local');
    expect($origins)->toContain('192.168.*');
});
