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

    /**
     * Consome de forma atômica: o UPDATE só é aplicado se ainda houver
     * quantidade suficiente no banco (WHERE quantidade >= N). Assim, dois
     * pedidos simultâneos não conseguem estourar o estoque.
     *
     * Retorna false quando não há pão suficiente (nada é alterado).
     */
    public function consumirSeDisponivel(int $quantidade): bool
    {
        if ($quantidade <= 0) {
            return true;
        }

        $linhasAfetadas = static::query()
            ->whereKey($this->getKey())
            ->where('quantidade', '>=', $quantidade)
            ->decrement('quantidade', $quantidade);

        $this->refresh();

        return $linhasAfetadas > 0;
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
