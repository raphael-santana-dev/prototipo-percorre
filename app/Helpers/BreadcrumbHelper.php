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
        $routeName = Route::currentRouteName();
        if (!$routeName) return [];

        $segments = explode('.', $routeName); 
        $breadcrumbs = [];
        $total = count($segments);

        foreach ($segments as $index => $segment) {
            $isLast = ($index === $total - 1);
            
            if ($isLast && $tituloPersonalizado) {
                $label = $tituloPersonalizado;
            } else {
                $label = match($segment) {
                    'index' => 'Listagem',
                    'show' => 'Detalhes',
                    'create' => 'Novo Cadastro',
                    'edit' => 'Editar',
                    default => Str::title(str_replace('-', ' ', $segment))
                };
            }

            $url = '#'; 
            if (!$isLast) {
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