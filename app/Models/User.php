<?php

namespace App\Models;

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
}
