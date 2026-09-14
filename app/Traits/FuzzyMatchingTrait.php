<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait FuzzyMatchingTrait
{
    
    public function calcularCompatibilidade(string $textoPlanilha, string $textoBanco): float
    {
        $limparEQuebrar = function ($texto) {
            $texto = preg_replace('/([a-zà-ú])([A-ZÀ-Ú])/', '$1 $2', $texto);
            
           
            $texto = str_replace(['-', '/', '_', ',', '|', ';', '\\', "\r", "\n"], ' ', $texto);
            
            $textoLimpo = strtolower(Str::ascii($texto));
            
            $textoLimpo = preg_replace('/[^a-z0-9 ]/', '', $textoLimpo);
            
            $palavras = array_filter(explode(' ', $textoLimpo));
            
            $conectivos = ['de', 'do', 'da', 'dos', 'das', 'com', 'em', 'para', 'e', 'ou', 'o', 'a', 'os', 'as'];
            $palavrasUteis = array_diff($palavras, $conectivos);
            
            return array_values(array_unique($palavrasUteis));
        };

        $palavrasPlanilha = $limparEQuebrar($textoPlanilha);
        $palavrasBanco = $limparEQuebrar($textoBanco);

        if (empty($palavrasPlanilha) || empty($palavrasBanco)) {
            return 0;
        }

        $intersecoes = array_intersect($palavrasPlanilha, $palavrasBanco);
        
        return (count($intersecoes) / count($palavrasPlanilha)) * 100;
    }
}