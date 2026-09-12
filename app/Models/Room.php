<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['nome', 'codigo', 'senha', 'criado_por', 'status'];

    protected $hidden = ['senha'];

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user')
            ->withPivot('papel')
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    public function isGarcom(int $userId): bool
    {
    return $this->participantes()
        ->where('user_id', $userId)
        ->wherePivot('papel', 'garcom')
        ->exists();
    }
}