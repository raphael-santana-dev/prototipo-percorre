<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class ComunicacaoLogEvento extends Model
{
    protected $table = 'comunicacao_log_eventos';
    protected $guarded = [];

    public function log()
    {
        return $this->belongsTo(ComunicacaoLog::class, 'comunicacao_log_id');
    }

    public function comunicado()
    {
        return $this->belongsTo(Comunicado::class, 'comunicado_id');
    }
}