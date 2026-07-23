<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Tenantable
{
    protected static function bootTenantable()
    {
        if (auth()->check()) {
            static::addGlobalScope('unidade_id', function (Builder $builder) {
                $user = auth()->user();
                
                // Se for DEV ou tiver a permissão de ver TODAS, não aplica o filtro
                if ($user->hasRole('dev') || $user->hasPermissionTo('unidade.visualizar.todas')) {
                    return;
                }

                // Se tiver permissões específicas (ex: unidade.visualizar.2), incluímos na busca
                // Para MVP, garantimos que ele veja no mínimo a dele própria
                $unidadesPermitidas = [$user->unidade_id];
                
                // Exemplo de expansão para múltiplas unidades específicas:
                // foreach($user->permissions as $perm) { se for 'unidade.visualizar.X', add no array }

                $builder->whereIn('unidade_id', $unidadesPermitidas);
            });
        }
    }

    public function unidade()
    {
        return $this->belongsTo(\App\Modules\Unidade\Domain\Models\Unidade::class, 'unidade_id');
    }
}