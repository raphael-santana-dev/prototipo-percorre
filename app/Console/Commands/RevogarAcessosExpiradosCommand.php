<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\RevogarPermissoesExpiradasJob;

class RevogarAcessosExpiradosCommand extends Command
{
    protected $signature = 'usuarios:revogar-acessos-expirados';
    protected $description = 'Remove permissões extras, vínculos acadêmicos (Unidade/Curso/Turno) e Centros de Custo com prazo de validade vencido.';

    public function handle(): int
    {
        $job = new RevogarPermissoesExpiradasJob();
        $resultado = $job->handle();

        $this->info(sprintf(
            'Limpeza concluída: %d permissão(ões) extra(s), %d vínculo(s) acadêmico(s) e %d centro(s) de custo expirado(s) removidos.',
            $resultado['permissoes_revogadas'] ?? 0,
            $resultado['vinculos_academicos_revogados'] ?? 0,
            $resultado['centros_custo_revogados'] ?? 0
        ));

        return self::SUCCESS;
    }
}