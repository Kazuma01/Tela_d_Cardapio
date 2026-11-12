<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Grava eventos de auditoria sem nunca derrubar a aplicação.
 *
 * Regras:
 * - não grava senha, token, cookie, segredo nem corpo de requisição;
 * - guarda apenas usuário, evento, descrição, IP, user-agent e metadados;
 * - se a gravação falhar, só registra um aviso técnico (sem dados sensíveis).
 */
class AuditLogger
{
    public static function log(
        string $event,
        ?string $description = null,
        array $metadata = [],
        ?int $userId = null,
    ): void {
        try {
            $request = request();

            AuditLog::create([
                'user_id' => $userId ?? Auth::id(),
                'event' => $event,
                'description' => $description !== null ? Str::limit($description, 250, '') : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent()
                    ? Str::limit((string) $request->userAgent(), 250, '')
                    : null,
                'metadata' => static::limpar($metadata) ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('audit_log_falhou', [
                'event' => $event,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove chaves sensíveis e limita o tamanho dos textos.
     */
    private static function limpar(array $metadata): array
    {
        $sensiveis = array_map('strtolower', config('audit.sensitive_keys', []));

        $limpar = function ($valor) use (&$limpar, $sensiveis) {
            if (is_array($valor)) {
                $saida = [];

                foreach ($valor as $chave => $item) {
                    if (is_string($chave) && in_array(strtolower($chave), $sensiveis, true)) {
                        $saida[$chave] = '[removido]';
                    } else {
                        $saida[$chave] = $limpar($item);
                    }
                }

                return $saida;
            }

            if (is_string($valor)) {
                return Str::limit($valor, 500, '…');
            }

            if (is_object($valor)) {
                return '[objeto]';
            }

            return $valor;
        };

        return $limpar($metadata);
    }
}
