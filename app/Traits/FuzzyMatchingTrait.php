<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait FuzzyMatchingTrait
{
    /**
     * Compara dois textos e retorna o percentual de similaridade (0 a 100).
     * Ele quebra os textos em arrays de palavras, remove conectivos, e avalia a interseção.
     */
    public function calcularCompatibilidade(string $textoPlanilha, string $textoBanco): float
    {
        $limparEQuebrar = function ($texto) {
            // Remove acentos e converte para minúsculas
            $textoLimpo = strtolower(Str::ascii($texto));
            // Remove pontuações mantendo apenas letras, números e espaços
            $textoLimpo = preg_replace('/[^a-z0-9 ]/', '', $textoLimpo);
            // Quebra em array de palavras
            $palavras = array_filter(explode(' ', $textoLimpo));
            
            // Remove palavras de ligação (Stop Words)
            $conectivos = ['de', 'do', 'da', 'dos', 'das', 'com', 'em', 'para', 'e', 'ou', 'o', 'a', 'os', 'as'];
            return array_values(array_diff($palavras, $conectivos));
        };

        $palavrasPlanilha = $limparEQuebrar($textoPlanilha);
        $palavrasBanco = $limparEQuebrar($textoBanco);

        if (empty($palavrasPlanilha) || empty($palavrasBanco)) {
            return 0;
        }

        // Calcula quantas palavras do texto da planilha existem no texto do banco
        $intersecoes = array_intersect($palavrasPlanilha, $palavrasBanco);
        
        return (count($intersecoes) / count($palavrasPlanilha)) * 100;
    }
}