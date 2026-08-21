<?php

namespace App\Modules\Unidade\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Curso;
use App\Traits\RegistraAuditoria;

class Unidade extends Model
{
    use HasFactory, SoftDeletes;
    use RegistraAuditoria;

    protected $fillable = [
        'nome', 'slug', 'status', 'data_inauguracao', 'endereco', 'email', 'telefone', 'foto_path',
        'cep', 'estado', 'cidade', 'bairro', 'logradouro', 'numero', 'complemento' // <- Adicionados
    ];
    
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'unidade_user');
    }

    // NOVA Relação (Cursos ministrados na Unidade)
    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'curso_unidade');
    }

    // Relacionamento com os Ciclos (Processos Seletivos)
    public function ciclos()
    {
        return $this->belongsToMany(\App\Models\Ciclo::class, 'ciclo_unidade', 'unidade_id', 'ciclo_id');
    }
}