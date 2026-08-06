<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\RegistraAuditoria;


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
        ];
    }

    /**
     * Unidades vinculadas a este usuário
     */
    public function unidades()
    {
        // Ajuste o namespace da classe Unidade caso necessário
        return $this->belongsToMany(\App\Modules\Unidade\Domain\Models\Unidade::class, 'unidade_user');
    }

    /**
     * Cursos vinculados a este usuário
     */
    public function cursos()
    {
        return $this->belongsToMany(\App\Models\Curso::class, 'curso_user');
    }

    /**
     * Turnos vinculados a este usuário
     */
    public function turnos()
    {
        // Ajuste o namespace da classe Turno caso necessário
        return $this->belongsToMany(\App\Modules\Turno\Domain\Models\Turno::class, 'turno_user');
    }
    
    /**
     * Helper para verificar se o usuário tem a permissão de Visão Global
     */
    /**
     * Helper para verificar se o usuário tem a permissão de Visão Global
     * Agora aceita o nome do módulo para checagem exata (ex: 'inscricoes', 'estudantes')
     */
    public function temVisaoGlobal(?string $modulo = null): bool
    {
        // 1. Se for admin ou dev, tem acesso total imediato a tudo
        if ($this->hasRole('dev|admin')) {
            return true;
        }

        // 2. Monta o nome exato da permissão. Ex: 'inscricoes.visao_global'
        if ($modulo) {
            $permissaoExata = "{$modulo}.visao_global";
            
            // Usamos a coleção em memória do Spatie. 
            // Assim não dá erro no banco se a permissão ainda não existir!
            return $this->getAllPermissions()->where('name', $permissaoExata)->isNotEmpty();
        }

        // Fallback de segurança (se não informar o módulo, nega por precaução)
        return false;
    }
}
