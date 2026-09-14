<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Importacao extends Model
{
    protected $table = 'importacoes';
    protected $guarded = [];

    protected $casts = [
        'mapeamento' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function getProgressoAttribute()
    {
        if ($this->total_linhas === 0 && in_array($this->status, ['concluido', 'erro'])) return 100;
        if ($this->total_linhas === 0) return 0;
        return min(100, round(($this->linhas_processadas / $this->total_linhas) * 100));
    }

    public function getStatusVisualAttribute()
    {
        return match($this->status) {
            'mapeamento' => ['cor' => 'bg-gray-100 text-gray-700 border-gray-200', 'icone' => 'ph-map-trifold', 'label' => 'Aguardando Mapeamento'],
            'na_fila' => ['cor' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'icone' => 'ph-hourglass', 'label' => 'Na Fila'],
            'processando' => ['cor' => 'bg-blue-100 text-blue-800 border-blue-200', 'icone' => 'ph-spinner animate-spin', 'label' => 'Processando...'],
            'concluido' => ['cor' => 'bg-green-100 text-green-800 border-green-200', 'icone' => 'ph-check-circle', 'label' => 'Concluído'],
            'erro_parcial' => ['cor' => 'bg-orange-100 text-orange-800 border-orange-200', 'icone' => 'ph-warning-circle', 'label' => 'Concluído com Alertas'],
            'erro' => ['cor' => 'bg-red-100 text-red-800 border-red-200', 'icone' => 'ph-x-circle', 'label' => 'Falha Crítica'],
            default => ['cor' => 'bg-gray-100 text-gray-800 border-gray-200', 'icone' => 'ph-question', 'label' => $this->status],
        };
    }

    public function getFormatoIconeAttribute()
    {
        return match(strtolower($this->formato)) {
            'csv' => 'text-green-600',
            'xlsx', 'xls' => 'text-emerald-600',
            'json' => 'text-yellow-600',
            'xml' => 'text-orange-600',
            default => 'text-gray-500',
        };
    }
}