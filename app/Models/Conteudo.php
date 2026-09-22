<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conteudo extends Model
{
    use SoftDeletes;

    protected $table = 'conteudos';

    protected $fillable = [
        'categoria_id', 'autor_id', 'titulo', 'slug', 'tipo', 'corpo',
        'is_active', 'data_inicio', 'data_fim', 'publico_alvo',
        'is_destaque', 'ordem_destaque', 'banner_interno', 'banner_desktop',
        'banner_mobile', 'banner_destaque', 'texto_overlay', 'opcoes_visuais'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_destaque' => 'boolean',
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
        'publico_alvo' => 'array',
        'opcoes_visuais' => 'array',
    ];

    public function categoria()
    {
        return $this->belongsTo(ConteudoCategoria::class, 'categoria_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    // Escopo de Validação de Tempo
    public function scopePublicados($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('data_inicio')->orWhere('data_inicio', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('data_fim')->orWhere('data_fim', '>=', now());
                     });
    }
}