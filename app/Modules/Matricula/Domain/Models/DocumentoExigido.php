<?php

namespace App\Modules\Matricula\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Ciclo;

class DocumentoExigido extends Model
{
    protected $table = 'documentos_exigidos';
    protected $guarded = ['id'];

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
    }
}