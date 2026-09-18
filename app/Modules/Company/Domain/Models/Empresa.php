<?php

namespace App\Modules\Company\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraAuditoria;

class Empresa extends Model
{
    use SoftDeletes;
    use RegistraAuditoria;

    protected $table = 'empresas';

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'is_active',
    ];

    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class, 'empresa_id');
    }

    public function gestores()
    {
        return $this->hasMany(CompanyUser::class, 'empresa_id')->where('tipo_acesso', 'gestor_avaliador');
    }

    public function aprendizes()
    {
        return $this->hasMany(\App\Modules\Student\Domain\Models\Student::class, 'empresa_id');
    }
}