<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ErrorLog extends Model
{
    protected $fillable = [
        'tipo',
        'http_code',
        'mensagem',
        'arquivo',
        'linha',
        'url',
        'metodo_http',
        'user_id',
        'stack_trace',
        'resolvido',
        'ocorrencias_count',
        'reaberto',
        'ultima_ocorrencia_em',
        'ocorrencias_historico',
    ];

    protected $casts = [
        'resolvido'             => 'boolean',
        'reaberto'              => 'boolean',
        'ocorrencias_count'     => 'integer',
        'ultima_ocorrencia_em'  => 'datetime',
        'ocorrencias_historico' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Intercepta a criação do log de erro para agrupar erros repetidos:
     * - Se já existir e não estiver resolvido: soma +1 em ocorrencias_count e adiciona ao histórico.
     * - Se já existir e estiver resolvido: reabre o erro (resolvido = false, reaberto = true) e soma +1.
     */
    public static function create(array $attributes = [])
    {
        $temColunasNovas = Schema::hasColumn('error_logs', 'ocorrencias_count');

        $req = request();
        $userId = $attributes['user_id'] ?? auth()->id();
        $userModel = $userId ? User::find($userId) : auth()->user();

        $novaOcorrencia = [
            'data_hora'   => now()->format('d/m/Y H:i:s'),
            'ip'          => $req?->ip() ?? '127.0.0.1 (CLI)',
            'user_id'     => $userId,
            'user_nome'   => $userModel?->name ?? 'Convidado / Sistema',
            'user_email'  => $userModel?->email ?? '-',
            'navegador'   => $req?->userAgent() ?? 'Console / Worker',
            'url'         => $attributes['url'] ?? ($req?->fullUrl() ?? 'CLI'),
            'metodo_http' => $attributes['metodo_http'] ?? ($req?->method() ?? 'CLI'),
        ];

        if ($temColunasNovas && !empty($attributes['mensagem'])) {
            $existente = static::query()
                ->where('mensagem', $attributes['mensagem'])
                ->where('arquivo', $attributes['arquivo'] ?? null)
                ->where('linha', $attributes['linha'] ?? null)
                ->latest('id')
                ->first();

            if ($existente) {
                $estavaResolvido = (bool) $existente->resolvido;
                $historico = is_array($existente->ocorrencias_historico) ? $existente->ocorrencias_historico : [];

                // Se era um registro legado que ainda não tinha a 1ª ocorrência no JSON, preserva os dados originais
                if (empty($historico)) {
                    $historico[] = [
                        'data_hora'   => $existente->created_at ? $existente->created_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s'),
                        'ip'          => 'Registro inicial',
                        'user_id'     => $existente->user_id,
                        'user_nome'   => $existente->user?->name ?? 'Convidado / Sistema',
                        'user_email'  => $existente->user?->email ?? '-',
                        'navegador'   => '-',
                        'url'         => $existente->url ?? 'CLI',
                        'metodo_http' => $existente->metodo_http ?? 'CLI',
                        'evento'      => 'Primeira ocorrência',
                    ];
                }

                $novaOcorrencia['evento'] = $estavaResolvido ? 'Erro Reaberto' : 'Recorrência';

                // Insere a ocorrência mais recente no topo da lista (mantendo até as últimas 150 ocorrências)
                array_unshift($historico, $novaOcorrencia);
                $historico = array_slice($historico, 0, 150);

                $existente->update([
                    'ocorrencias_count'     => max(1, (int) $existente->ocorrencias_count) + 1,
                    'resolvido'             => false,
                    'reaberto'              => $estavaResolvido ? true : (bool) $existente->reaberto,
                    'ultima_ocorrencia_em'  => now(),
                    'ocorrencias_historico' => $historico,
                    'user_id'               => $userId ?? $existente->user_id,
                    'url'                   => $attributes['url'] ?? $existente->url,
                    'metodo_http'           => $attributes['metodo_http'] ?? $existente->metodo_http,
                    'stack_trace'           => $attributes['stack_trace'] ?? $existente->stack_trace,
                ]);

                return $existente;
            }

            $novaOcorrencia['evento'] = 'Primeira ocorrência';
            $attributes['ocorrencias_count']     = 1;
            $attributes['reaberto']              = false;
            $attributes['ultima_ocorrencia_em']  = now();
            $attributes['ocorrencias_historico'] = [$novaOcorrencia];
        }

        return static::query()->create($attributes);
    }
}