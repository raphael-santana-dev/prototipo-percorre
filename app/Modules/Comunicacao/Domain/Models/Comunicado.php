<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Comunicado extends Model
{
    
    use RegistraAuditoria;
    protected $table = 'comunicados';
    protected $guarded = [];
    protected $casts = [
        'destinatarios' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'anexos' => 'array',
        'data_agendamento' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }
    
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pendente' => 'bg-yellow-100 text-yellow-800',
            'enviando' => 'bg-blue-100 text-blue-800',
            'concluido' => 'bg-green-100 text-green-800',
            'erro' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}