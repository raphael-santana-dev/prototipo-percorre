<?php

namespace App\Modules\Company\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use SoftDeletes;

    protected $table = 'empresas';

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'is_active',
    ];

    // Todos os usuários corporativos desta empresa (Contatos e Gestores)
    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class, 'empresa_id');
    }

    // Apenas os Gestores Avaliadores
    public function gestores()
    {
        return $this->hasMany(CompanyUser::class, 'empresa_id')->where('tipo_acesso', 'gestor_avaliador');
    }

    // Todos os alunos aprendizes desta empresa
    public function aprendizes()
    {
        return $this->hasMany(\App\Modules\Student\Domain\Models\Student::class, 'empresa_id');
    }
}