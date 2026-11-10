<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreadStock extends Model
{
    protected $fillable = ['quantidade'];

    /**
     * Retorna o contador global de pão (cria com estoque inicial na primeira vez).
     */
    public static function atual(): self
    {
        return static::query()->firstOrCreate([], ['quantidade' => 100]);
    }

    public function consumir(int $quantidade): void
    {
        $this->decrement('quantidade', $quantidade);
    }

    public function repor(int $quantidade): void
    {
        $this->increment('quantidade', $quantidade);
    }

    public function definir(int $quantidade): void
    {
        $this->update(['quantidade' => $quantidade]);
    }
}
