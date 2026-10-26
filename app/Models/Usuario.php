<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Usuario de SIVENT. La tabla de Edgar usa "usuario" y "contrasena_hash"
 * en lugar de los email/password de Laravel.
 */
class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $guarded = [];

    protected $hidden = ['contrasena_hash'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function getAuthPassword()
    {
        return $this->contrasena_hash;
    }

    public function getAuthPasswordName()
    {
        return 'contrasena_hash';
    }

    // La tabla no tiene remember_token.
    public function getRememberTokenName()
    {
        return '';
    }
}
