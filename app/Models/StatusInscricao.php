<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class StatusInscricao extends Model
{
    use RegistraAuditoria;
    protected $table = 'status_inscricoes'; // Define a tabela explicitamente
    
    protected $fillable = [
        'nome', 'descricao', 'cor', 'slug', 'status'
    ];

    // Mágica do Laravel: Intercepta a criação no banco
    protected static function booted()
    {
        static::creating(function ($status) {
            if (empty($status->cor)) {
                $status->cor = self::gerarCorSegura();
            }
        });
    }

    public function ciclos()
    {
        return $this->belongsToMany(Ciclo::class, 'ciclo_status_inscricao');
    }

    public static function gerarCorSegura()
    {
        $coresSeguras = [
            '#3B82F6', // Blue
            '#10B981', // Emerald
            '#8B5CF6', // Violet
            '#F59E0B', // Amber
            '#EC4899', // Pink
            '#14B8A6', // Teal
            '#6366F1', // Indigo
            '#F43F5E', // Rose
            '#84CC16', // Lime
            '#06B6D4', // Cyan
        ];
        
        return $coresSeguras[array_rand($coresSeguras)];
    }
}