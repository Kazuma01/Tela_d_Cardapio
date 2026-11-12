<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Limpa registros de auditoria mais antigos que AUDIT_RETENTION_DAYS (90 por padrão).
Schedule::command('model:prune')->dailyAt('03:30');
