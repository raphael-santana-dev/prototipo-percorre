<?php

namespace App\Modules\Student\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\FiltraPorVinculo;

use App\Traits\RegistraAuditoria;

class Student extends Authenticatable
{
    use HasFactory, Notifiable, FiltraPorVinculo;
    use RegistraAuditoria;

    public $moduloPermissao = 'students';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'unidade_id',
        'cpf',
        'slug',
        'is_aprendiz',
        'empresa_id',
        'gestor_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_aprendiz' => 'boolean',
    ];

    public function unidade()
    {
        return $this->belongsTo(\App\Modules\Unidade\Domain\Models\Unidade::class, 'unidade_id');
    }

    public function gestor()
    {
        return $this->belongsTo(\App\Modules\Company\Domain\Models\CompanyUser::class, 'gestor_id');
    }

    public function empresa()
    {
        return $this->belongsTo(\App\Modules\Company\Domain\Models\Empresa::class, 'empresa_id');
    }
}