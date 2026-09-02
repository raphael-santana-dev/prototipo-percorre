<?php

namespace App\Modules\Matricula\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inscricao;
use App\Models\User;

class DocumentoMatricula extends Model
{
    protected $table = 'documentos_matricula';
    protected $guarded = ['id'];
    protected $casts = [
        'log_ia' => 'array',
    ];

    public function inscricao()
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function documentoExigido()
    {
        return $this->belongsTo(DocumentoExigido::class);
    }

    public function avaliador()
    {
        return $this->belongsTo(User::class, 'avaliado_por');
    }
}