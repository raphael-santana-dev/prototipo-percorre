<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class BreadcrumbHelper
{
    public static function generate()
    {
        $routeName = Route::currentRouteName(); // Ex: admin.cursos.detalhes
        $segments = explode('.', $routeName); // ['admin', 'cursos', 'detalhes']
        
        $breadcrumbs = [];
        $url = '';

        foreach ($segments as $segment) {
            $url .= '/' . $segment;
            $breadcrumbs[] = [
                'label' => Str::title($segment), // Transforma 'cursos' em 'Cursos'
                'url' => ($segment === 'admin') ? '/admin' : $url
            ];
        }

        return $breadcrumbs;
    }
}