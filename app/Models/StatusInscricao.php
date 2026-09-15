<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class StatusInscricao extends Model
{
    use RegistraAuditoria;
    protected $table = 'status_inscricoes';
    
    protected $fillable = [
        'nome', 'descricao', 'cor', 'slug', 'status'
    ];

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
            '#3B82F6',
            '#10B981',
            '#8B5CF6',
            '#F59E0B',
            '#EC4899',
            '#14B8A6',
            '#6366F1',
            '#F43F5E',
            '#84CC16',
            '#06B6D4',
        ];
        
        return $coresSeguras[array_rand($coresSeguras)];
    }
}