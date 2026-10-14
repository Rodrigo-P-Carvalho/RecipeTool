<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

/**
 * Modelo de domínio da tabela users usada pela autenticação Laravel.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class Usuario extends User
{
    protected $table = 'users';
}
