<?php

namespace App\Modules\Matricula\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Ciclo;
use App\Traits\RegistraAuditoria;

class DocumentoExigido extends Model
{
    use RegistraAuditoria;
    protected $table = 'documentos_exigidos';
    protected $guarded = ['id'];

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
    }
}