<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Conteudo extends Model
{
    use SoftDeletes;

    protected $table = 'conteudos';

    protected $fillable = [
        'categoria_id', 'autor_id', 'titulo', 'slug', 'tipo', 'corpo',
        'is_active', 'data_inicio', 'data_fim', 'publico_alvo',
        'is_destaque', 'ordem_destaque', 'banner_interno', 'banner_desktop',
        'banner_mobile', 'banner_destaque', 'texto_overlay', 'opcoes_visuais',
        'visualizacoes'
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

    // 1. ESCOPO DE VALIDAÇÃO DE TEMPO E STATUS
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

    // 2. MOTOR DE ACESSO: ESCOPO DE VALIDAÇÃO POR GUARD/PÚBLICO
    public function scopeAutorizado($query)
    {
        return $query->where(function ($q) {
            // Regra Ouro: Se for "geral", todos podem ver (logados ou não)
            $q->whereJsonContains('publico_alvo', 'geral');

            // Verifica as sessões activas para liberar acessos restritos
            if (Auth::check()) {
                $user = Auth::user();
                $classeUtilizador = class_basename($user);

                // Se o utilizador logado for do tipo Student (Estudantes)
                if ($classeUtilizador === 'Student') {
                    $q->orWhereJsonContains('publico_alvo', 'estudantes');
                }
                
                // Se o utilizador logado for do tipo ContactUser/Empresa (Empresas)
                elseif ($classeUtilizador === 'ContactUser' || $classeUtilizador === 'Contato') {
                    $q->orWhereJsonContains('publico_alvo', 'empresas');
                }
                
                // Se o utilizador logado for do tipo User (Colaboradores/Interno)
                elseif ($classeUtilizador === 'User') {
                    $q->orWhereJsonContains('publico_alvo', 'interno');
                }
            }
        });
    }
}