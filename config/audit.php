<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retenção da auditoria
    |--------------------------------------------------------------------------
    |
    | Quantos dias os registros de auditoria ficam guardados. A limpeza roda
    | diariamente pelo scheduler (php artisan model:prune).
    |
    */
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Chaves que nunca vão para a auditoria
    |--------------------------------------------------------------------------
    |
    | Mesmo que alguém passe esses campos por engano nos metadados, eles são
    | removidos antes de gravar. Senhas, tokens e cookies nunca são logados.
    |
    */
    'sensitive_keys' => [
        'password',
        'password_confirmation',
        'current_password',
        'senha',
        'token',
        'remember_token',
        'secret',
        'authorization',
        'cookie',
        'api_key',
    ],

];
