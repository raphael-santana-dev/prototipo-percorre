<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class Automacao extends Model
{
    protected $table = 'automacoes';
    protected $guarded = [];
    protected $casts = [
        'status' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }
}