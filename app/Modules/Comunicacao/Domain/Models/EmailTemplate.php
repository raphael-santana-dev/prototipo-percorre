<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class EmailTemplate extends Model
{
    protected $table = 'email_templates';

    protected $fillable = [
        'nome',
        'canal',
        'status',
        'assunto',
        'corpo',
        'botoes',
        'suri_template_id',
        'configuracoes_suri',
    ];

    protected $casts = [
        'status'             => 'boolean',
        'botoes'             => 'array',
        'configuracoes_suri' => 'array',
    ];

    public function scopeAtivos($query)
    {
        if (Schema::hasColumn('email_templates', 'status')) {
            return $query->where('status', true);
        }
        return $query;
    }

    public function automacoes()
    {
        return $this->hasMany(Automacao::class, 'template_id');
    }

    public function esteiraEtapas()
    {
        return $this->hasMany(EsteiraEtapa::class, 'template_id');
    }
}