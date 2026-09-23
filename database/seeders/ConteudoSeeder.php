<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conteudo;
use App\Models\ConteudoCategoria;
use App\Models\User;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ConteudoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        // 1. Garante que existe pelo menos um autor (Admin/Dev)
        $autor = User::first();
        if (!$autor) {
            $autor = User::create([
                'name' => 'Administrador do Portal',
                'email' => 'admin.portal@percorre.com.br',
                'password' => bcrypt('password'),
            ]);
        }

        // 2. Criação das Categorias
        $categoriasNomes = ['Notícias', 'Eventos', 'Dicas de Carreira', 'Comunicados Internos'];
        $categoriasIds = [];
        foreach ($categoriasNomes as $nome) {
            $cat = ConteudoCategoria::firstOrCreate(
                ['slug' => Str::slug($nome)],
                ['nome' => $nome, 'is_active' => true]
            );
            $categoriasIds[] = $cat->id;
        }

        // 3. Opções de Públicos-Alvo
        $publicos = [
            ['geral'],
            ['estudantes'],
            ['empresas'],
            ['interno'],
            ['estudantes', 'empresas']
        ];

        // 4. Criação de 5 Destaques Principais (Home)
        for ($i = 1; $i <= 5; $i++) {
            Conteudo::create([
                'categoria_id' => $faker->randomElement($categoriasIds),
                'autor_id' => $autor->id,
                'titulo' => "Destaque Principal 0{$i}: " . $faker->sentence(4),
                'slug' => Str::slug("Destaque Principal 0{$i} " . Str::random(5)),
                'tipo' => 'padrao',
                'corpo' => '<p>' . implode('</p><p>', $faker->paragraphs(4)) . '</p>',
                'is_active' => true,
                'data_inicio' => now()->subDays(rand(1, 10)),
                'publico_alvo' => ['geral'], // Destaques costumam ser públicos
                'is_destaque' => true,
                'ordem_destaque' => $i,
                'texto_overlay' => 'Leia agora o nosso destaque',
                'visualizacoes' => rand(500, 3000),
            ]);
        }

        // 5. Criação de 5 Stories
        $slidesStory = [
            ['imagem_path' => null, 'texto' => 'Deslize para acompanhar esta novidade exclusiva!', 'posicao_texto' => 'center'],
            ['imagem_path' => null, 'texto' => 'O Instituto Percorre está a trazer novas ferramentas.', 'posicao_texto' => 'bottom'],
            ['imagem_path' => null, 'texto' => 'Aceda ao portal e confira o conteúdo completo.', 'posicao_texto' => 'top'],
        ];

        for ($i = 1; $i <= 5; $i++) {
            Conteudo::create([
                'categoria_id' => $faker->randomElement($categoriasIds),
                'autor_id' => $autor->id,
                'titulo' => "Web Story Exclusivo: " . $faker->sentence(3),
                'slug' => Str::slug("Story Exclusivo 0{$i} " . Str::random(5)),
                'tipo' => 'story',
                'corpo' => '', // Story não tem corpo de texto, apenas slides
                'is_active' => true,
                'data_inicio' => now()->subDays(rand(1, 5)),
                'publico_alvo' => $faker->randomElement($publicos),
                'is_destaque' => false,
                'visualizacoes' => rand(100, 1000),
                'opcoes_visuais' => ['slides' => $slidesStory],
            ]);
        }

        // 6. Criação de 5 Carrosséis/Galerias
        $slidesCarrossel = [
            ['imagem_path' => null, 'texto' => 'Foto 1 da nossa galeria de eventos.', 'posicao_texto' => 'bottom'],
            ['imagem_path' => null, 'texto' => 'Momentos marcantes da última semana.', 'posicao_texto' => 'bottom'],
            ['imagem_path' => null, 'texto' => 'Encerramento das atividades com sucesso.', 'posicao_texto' => 'bottom'],
        ];

        for ($i = 1; $i <= 5; $i++) {
            Conteudo::create([
                'categoria_id' => $faker->randomElement($categoriasIds),
                'autor_id' => $autor->id,
                'titulo' => "Galeria de Fotos: " . $faker->sentence(3),
                'slug' => Str::slug("Galeria de Fotos 0{$i} " . Str::random(5)),
                'tipo' => 'carrossel',
                'corpo' => '<p class="text-center">Confira os melhores momentos desta iniciativa através da nossa galeria interativa.</p>',
                'is_active' => true,
                'data_inicio' => now()->subDays(rand(1, 15)),
                'publico_alvo' => $faker->randomElement($publicos),
                'is_destaque' => false,
                'visualizacoes' => rand(200, 800),
                'opcoes_visuais' => ['slides' => $slidesCarrossel],
            ]);
        }

        // 7. Criação de 10 Notícias Padrão (Grelha Geral)
        for ($i = 1; $i <= 10; $i++) {
            Conteudo::create([
                'categoria_id' => $faker->randomElement($categoriasIds),
                'autor_id' => $autor->id,
                'titulo' => "Notícia Institucional 0{$i}: " . $faker->sentence(5),
                'slug' => Str::slug("Noticia Padrao 0{$i} " . Str::random(5)),
                'tipo' => 'padrao',
                'corpo' => '<h2>Visão Geral</h2><p>' . implode('</p><p>', $faker->paragraphs(3)) . '</p><h2>Impacto</h2><p>' . implode('</p><p>', $faker->paragraphs(2)) . '</p>',
                'is_active' => true,
                'data_inicio' => now()->subDays(rand(1, 30)), // Datas variadas para testar ordenação
                'publico_alvo' => $faker->randomElement($publicos),
                'is_destaque' => false,
                'visualizacoes' => rand(10, 500),
            ]);
        }
    }
}