<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\RegistraAuditoria;
use App\Notifications\CustomResetPasswordNotification;


class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    use RegistraAuditoria;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cpf',
        'slug',
        'codigo_protheus',
        'must_change_password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean'
        ];
    }

    public function unidades()
    {
        return $this->belongsToMany(\App\Modules\Unidade\Domain\Models\Unidade::class, 'unidade_user');
    }

    public function cursos()
    {
        return $this->belongsToMany(\App\Models\Curso::class, 'curso_user');
    }

    
    public function turnos()
    {
        return $this->belongsToMany(\App\Modules\Turno\Domain\Models\Turno::class, 'turno_user');
    }   
    
    public function temVisaoGlobal(?string $modulo = null): bool
    {
        if ($this->hasRole('dev|admin')) {
            return true;
        }

        if ($modulo) {
            $permissaoExata = "{$modulo}.visao_global";
            
            return $this->getAllPermissions()->where('name', $permissaoExata)->isNotEmpty();
        }

        return false;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    public function centros_de_custo()
    {
        return $this->belongsToMany(\App\Modules\Financeiro\Domain\Models\CentroCusto::class, 'centro_custo_user')
                    ->withPivot(['tipo_acesso', 'expires_at'])
                    ->withTimestamps();
    }

    /**
     * Relacionamento 1: Centros de Custo vinculados para EDIÇÃO de orçamento
     */
    public function centros_de_custo_edicao()
    {
        return $this->belongsToMany(\App\Modules\Financeiro\Domain\Models\CentroCusto::class, 'centro_custo_user')
                    ->wherePivot('tipo_acesso', 'edicao')
                    ->withPivot(['tipo_acesso', 'expires_at'])
                    ->withTimestamps();
    }

    /**
     * Relacionamento 2: Centros de Custo vinculados para APROVAÇÃO de orçamento
     */
    public function centros_de_custo_aprovacao()
    {
        return $this->belongsToMany(\App\Modules\Financeiro\Domain\Models\CentroCusto::class, 'centro_custo_user')
                    ->wherePivot('tipo_acesso', 'aprovacao')
                    ->withPivot(['tipo_acesso', 'expires_at'])
                    ->withTimestamps();
    }

    /**
     * Verifica se o usuário possui a opção "Permissão Global de Centros de Custo" ativa
     */
    public function possuiVisaoGlobalCentrosCusto(?string $tipo = null): bool
    {
        if ($this->hasRole('dev')) {
            return true;
        }

        if ($tipo === 'edicao' && $this->can('centros_custo.visao_global.edicao')) {
            return true;
        }

        if ($tipo === 'aprovacao' && $this->can('centros_custo.visao_global.aprovacao')) {
            return true;
        }

        return $this->temVisaoGlobal('centros_custo')
            || $this->can('centros_custo.visao_global');
    }

    /**
     * Retorna os códigos de Centros de Custo que este usuário pode EDITAR
     * (Retorna null se tiver acesso global a todos os CCs)
     */
    public function obterCodigosCcEdicao(): ?array
    {
        if ($this->possuiVisaoGlobalCentrosCusto('edicao')) {
            return null; // null = Todos os Centros de Custo
        }

        return $this->centros_de_custo_edicao()
            ->where(function ($q) {
                $q->whereNull('centro_custo_user.expires_at')
                  ->orWhereDate('centro_custo_user.expires_at', '>=', now()->toDateString());
            })
            ->pluck('centros_custo.codigo')
            ->toArray();
    }

    /**
     * Retorna os códigos de Centros de Custo que este usuário pode APROVAR
     * (Retorna null se tiver acesso global a todos os CCs)
     */
    public function obterCodigosCcAprovacao(): ?array
    {
        if ($this->possuiVisaoGlobalCentrosCusto('aprovacao')) {
            return null; // null = Todos os Centros de Custo
        }

        return $this->centros_de_custo_aprovacao()
            ->where(function ($q) {
                $q->whereNull('centro_custo_user.expires_at')
                  ->orWhereDate('centro_custo_user.expires_at', '>=', now()->toDateString());
            })
            ->pluck('centros_custo.codigo')
            ->toArray();
    }
}
