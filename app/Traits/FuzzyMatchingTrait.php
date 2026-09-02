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
            // 1. Separa palavras grudadas em CamelCase (Ex: "ManhãNoite" vira "Manhã Noite")
            $texto = preg_replace('/([a-zà-ú])([A-ZÀ-Ú])/', '$1 $2', $texto);
            
            // 2. Troca pontuações e quebras de linha por espaço 
            // Evita que "MG-Barreiro" vire "mgbarreiro"
            $texto = str_replace(['-', '/', '_', ',', '|', ';', '\\', "\r", "\n"], ' ', $texto);
            
            // 3. Remove acentos e converte para minúsculas
            $textoLimpo = strtolower(Str::ascii($texto));
            
            // 4. Remove qualquer coisa que não seja letra, número ou espaço
            $textoLimpo = preg_replace('/[^a-z0-9 ]/', '', $textoLimpo);
            
            // 5. Quebra em array de palavras e limpa espaços vazios
            $palavras = array_filter(explode(' ', $textoLimpo));
            
            // 6. Remove Stop Words (conectivos irrelevantes)
            $conectivos = ['de', 'do', 'da', 'dos', 'das', 'com', 'em', 'para', 'e', 'ou', 'o', 'a', 'os', 'as'];
            $palavrasUteis = array_diff($palavras, $conectivos);
            
            // 7. Retorna apenas as palavras únicas. 
            // (Evita que "SPSantanaSPSantana" conte como 4 palavras, o que baixaria o score)
            return array_values(array_unique($palavrasUteis));
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