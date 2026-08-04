<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class BreadcrumbHelper
{
    /**
     * @param string|null $tituloPersonalizado Opcional: Define o nome real do registro (Ex: "2027 - 2º Semestre")
     */
    public static function generate($tituloPersonalizado = null)
    {
        $routeName = Route::currentRouteName(); // Ex: ciclos.show
        if (!$routeName) return [];

        $segments = explode('.', $routeName); 
        $breadcrumbs = [];
        $total = count($segments);

        foreach ($segments as $index => $segment) {
            $isLast = ($index === $total - 1);
            
            // 1. Define o Rótulo (Label)
            if ($isLast && $tituloPersonalizado) {
                // Se for a última etapa e você passou um nome, usa ele!
                $label = $tituloPersonalizado;
            } else {
                // Se não, traduz os termos técnicos do Laravel para português
                $label = match($segment) {
                    'index' => 'Listagem',
                    'show' => 'Detalhes',
                    'create' => 'Novo Cadastro',
                    'edit' => 'Editar',
                    default => Str::title(str_replace('-', ' ', $segment))
                };
            }

            // 2. Define a URL de forma segura
            $url = '#'; // Para a aba ativa (última), não precisamos de link
            if (!$isLast) {
                // Tenta achar a rota de listagem oficial daquele módulo (Ex: ciclos.index)
                if (Route::has($segment . '.index')) {
                    $url = route($segment . '.index');
                } else {
                    $url = '/' . $segment;
                }
            }

            $breadcrumbs[] = [
                'label' => $label,
                'url' => $url
            ];
        }

        return $breadcrumbs;
    }
}