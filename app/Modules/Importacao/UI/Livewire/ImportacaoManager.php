<?php

namespace App\Modules\Importacao\UI\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Importacao;
use App\Models\Ciclo;
use App\Models\User;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;

class ImportacaoManager extends Component
{
    use WithFileUploads, WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];

    public $modalUploadAberto = false;
    public $modalMapeamentoAberto = false;
    public $modalDetalhesAberto = false;
    public $modalReprocessarAberto = false;
    public $importacaoReprocessarId = null;

    public $arquivo;
    public $tipoImportacao = '';
    public $cicloSelecionadoId = null;
    public $ciclosDisponiveis = [];

    public $importacaoAtualId = null;
    public $importacaoDetalhes = null;
    public $permitirAutoCadastro = false;
    public $mesclarDuplicadas = false;

    public $cabecalhos = [];
    public $mapeamento = [];
    
    // Filtros
    public $filtro_tipo = '';
    public $filtro_status = '';
    public $filtro_usuario = '';
    public $filtro_data_inicio = '';
    public $filtro_data_fim = '';

    // Mapeamento Dinâmico
    public $camposDinamicosDisponiveis = [];

    public array $previewCabecalhos = [];
    public array $previewDados = [];

    public $opcoesMapeamento = [
        'nome' => 'Nome Completo',
        'email' => 'E-mail',
        'cpf' => 'CPF',
        'celular' => 'Celular / WhatsApp',
        'data_nascimento' => 'Data de Nascimento',
        'possui_nome_social' => 'Possui Nome Social?',
        'nome_social' => 'Nome Social',
        'cep' => 'CEP',
        'logradouro' => 'Logradouro / Endereço',
        'numero' => 'Número',
        'complemento' => 'Complemento',
        'bairro' => 'Bairro',
        'cidade' => 'Cidade',
        'estado' => 'Estado (UF)',
        'unidade_id' => 'Sede/Unidade (Exige ID)',
        'curso_id' => 'Curso (Exige ID)',
        'turno_id' => 'Turno (Exige ID)',
        'status_inscricao_id' => 'Status da Inscrição (Exige ID)',
        'possui_deficiencia' => 'Possui Deficiência?',
        'natureza_deficiencia' => 'Natureza da Deficiência',
        'receber_informacoes' => 'Termo: Receber Informações (0/1)',
        'autorizacao_uso_infos' => 'Termo: Autorização de Uso de Dados (0/1)',
        'pontuacao_total' => 'Score / Pontuação Total',
        'posicao_ranking' => 'Posição no Ranking',
        'etapa_atual' => 'Progresso (Etapa Atual)',
    ];

    public function mount()
    {
        abort_if(!feature('importacao.acessar'), 403, 'Módulo de integrações desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('importacao.acessar'), 403, 'Acesso restrito.');

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->ciclosDisponiveis = Ciclo::orderBy('nome', 'asc')->get();
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_tipo', 'filtro_status', 'filtro_usuario', 'filtro_data_inicio', 'filtro_data_fim'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_tipo', 'filtro_status', 'filtro_usuario', 'filtro_data_inicio', 'filtro_data_fim']);
        $this->resetPage();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#', 'sortable' => false, 'class' => 'w-16'],
            ['key' => 'arquivo', 'label' => 'Arquivo / Tipo', 'sortable' => false],
            ['key' => 'progresso', 'label' => 'Status e Progresso', 'sortable' => false, 'class' => 'w-64'],
            ['key' => 'data', 'label' => 'Data de Envio', 'sortable' => false],
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'w-32 text-right'],
        ];
    }

    public function baixarTemplate($tipo)
    {
        
        if ($tipo === 'inscricoes') {
            $cabecalho = ['Nome', 'E-mail', 'CPF', 'Celular', 'Data de Nascimento', 'Estado', 'Unidade', 'Curso', 'Turno'];
            $exemplo = ['Maria Oliveira', 'maria@email.com', '123.456.789-00', '11999999999', '15/05/2000', 'SP', 'Unidade Paulista', 'Design Gráfico', 'Noturno'];
            return $this->gerarCsv('modelo_importacao_inscricoes.csv', [$cabecalho, $exemplo]);
        }
        if ($tipo === 'usuarios') {
            $cabecalho = ['Nome Completo', 'E-mail', 'CPF', 'Senha', 'Grupo de Acesso', 'Permissões Extras'];
            $exemplo = ['João Silva', 'joao@email.com', '123.456.789-00', 'senha123', 'estudante', 'ver_aulas, editar_perfil'];
            return $this->gerarCsv('modelo_importacao_usuarios.csv', [$cabecalho, $exemplo]);
        }
        if ($tipo === 'campos') {
            $cabecalho = ['Etapa', 'Ordem', 'Nome do Campo', 'ID no Banco', 'Tipo', 'Subtipo', 'Largura', 'Obrigatório', 'Sempre Visível?', 'Regras de Exibição', 'Opções'];
            $exemplo1 = ['1', '1', 'Unidade de Interesse', 'unidade_id', 'system', 'unidade', '12', 'Sim', 'Sim', '', ''];
            $exemplo2 = ['1', '2', 'Como nos conheceu?', 'como_conheceu', 'select', '', '12', 'Não', 'Sim', '', 'Instagram, Facebook, Amigos'];
            $exemplo3 = ['1', '3', 'Qual rede social?', 'qual_rede', 'text', 'text', '12', 'Sim', 'Não', 'como_conheceu=Instagram', ''];
            return $this->gerarCsv('modelo_importacao_campos.csv', [$cabecalho, $exemplo1, $exemplo2, $exemplo3]);
        }
        if ($tipo === 'unidades') {
            $cabecalho = ['Nome da Unidade', 'Estado', 'Cidade', 'Status'];
            $exemplo = ['Unidade Paulista', 'SP', 'São Paulo', 'Ativa'];
            return $this->gerarCsv('modelo_importacao_unidades.csv', [$cabecalho, $exemplo]);
        }
        if ($tipo === 'cursos') {
            $cabecalho = ['Nome do Curso', 'Status', 'Idade Mínima', 'Idade Máxima', 'Permite Estado Diferente?'];
            $exemplo = ['Design Gráfico', 'Ativo', '16', '99', 'Não'];
            return $this->gerarCsv('modelo_importacao_cursos.csv', [$cabecalho, $exemplo]);
        }
    }

    private function gerarCsv($nomeArquivo, $dados)
    {
        $callback = function() use ($dados) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); 
            foreach ($dados as $linha) {
                fputcsv($file, $linha, ';');
            }
            fclose($file);
        };
        return response()->streamDownload($callback, $nomeArquivo, ['Content-Type' => 'text/csv']);
    }

    public function abrirModalReprocessar($id)
    {
        $this->importacaoReprocessarId = $id;
        $this->modalReprocessarAberto = true;
    }

    public function reprocessar($modo)
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('importacao.acessar'), 403);

        $importacao = Importacao::findOrFail($this->importacaoReprocessarId);
        $mapa = $importacao->mapeamento;

        if ($modo === 'falhas') {
            // Extrai do log antigo apenas as linhas que falharam
            $erros = json_decode($importacao->erro_mensagem, true) ?? [];
            $linhasComErro = [];
            
            foreach ($erros as $erro) {
                if (isset($erro['linha']) && is_numeric($erro['linha'])) {
                    $linhasComErro[] = (int) $erro['linha'];
                }
            }

            if (empty($linhasComErro)) {
                $this->dispatch('erro', msg: 'Não encontramos linhas específicas com erro neste log para reprocessar de forma isolada.');
                return;
            }
            
            // Injeta as linhas a serem processadas no mapeamento (sem alterar a base de dados estrutural)
            $mapa['linhas_reprocessar'] = array_values(array_unique($linhasComErro));
        } else {
            // Modo "Tudo" - Limpa o array de linhas alvo se existir de uma tentativa passada
            if (isset($mapa['linhas_reprocessar'])) {
                unset($mapa['linhas_reprocessar']);
            }
        }

        // Reseta os status da importação como se ela tivesse acabado de ser enviada
        $importacao->update([
            'status' => 'na_fila',
            'linhas_processadas' => 0,
            'erro_mensagem' => null,
            'mapeamento' => $mapa
        ]);

        $this->reset(['modalReprocessarAberto', 'importacaoReprocessarId', 'modalDetalhesAberto', 'importacaoDetalhes']);
        $this->dispatch('sucesso', msg: 'A importação foi devolvida para a fila de processamento!');

        // Despacha o Background Job
        dispatch(new \App\Jobs\ProcessarImportacaoUniversalJob($importacao))->afterResponse();
    }

    public function abrirModalUpload()
    {
        $this->reset(['arquivo', 'tipoImportacao', 'cicloSelecionadoId', 'camposDinamicosDisponiveis', 'permitirAutoCadastro', 'mesclarDuplicadas']);
        $this->modalUploadAberto = true;
    }

    public function processarUpload()
    {
        
        if (!empty($this->tipoImportacao) || $this->tipoImportacao !== '') {
            $regras = ['arquivo' => 'required|mimes:csv,xlsx,xls,json,xml|max:51200']; 
            if ($this->tipoImportacao === 'campos' || $this->tipoImportacao === 'inscricoes') {
                $regras['cicloSelecionadoId'] = 'required';
            }
            $this->validate($regras, ['cicloSelecionadoId.required' => 'Obrigatório selecionar o ciclo para este tipo de importação.']);

            $ativos = Importacao::where('user_id', auth()->id())->whereIn('status', ['mapeamento', 'na_fila', 'processando'])->count();
            if ($ativos >= 5) {
                $this->addError('arquivo', 'Fila cheia! Aguarde a conclusão das importações anteriores.');
                return;
            }

            $extensao = $this->arquivo->getClientOriginalExtension();
            $caminho = $this->arquivo->store('importacoes', 'local'); 
            $caminhoAbsoluto = Storage::disk('local')->path($caminho);
            
            $totalLinhas = 0;
            $cabecalhosLidos = [];
            
            if (in_array(strtolower($extensao), ['csv', 'xlsx', 'xls'])) {
                if (strtolower($extensao) === 'csv') {
                    $primeiraLinha = fgets(fopen($caminhoAbsoluto, 'r'));
                    $delimiter = substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',') ? ';' : ',';
                    $reader = SimpleExcelReader::create($caminhoAbsoluto)->useDelimiter($delimiter);
                } else {
                    $reader = SimpleExcelReader::create($caminhoAbsoluto);
                }

                $headers = $reader->getHeaders() ?? [];
                foreach ($headers as $h) {
                    $cabecalhosLidos[] = mb_convert_encoding(trim($h), 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
                }
                $totalLinhas = $reader->getRows()->count();
            } else {
                $cabecalhosLidos = ['Dados Brutos'];
                $totalLinhas = 1; 
            }

            $importacao = Importacao::create([
                'user_id' => auth()->id(),
                'tipo' => $this->tipoImportacao,
                'operacao' => 'importacao',
                'formato' => strtolower($extensao),
                'arquivo_nome' => $this->arquivo->getClientOriginalName(),
                'arquivo_caminho' => $caminho,
                'total_linhas' => $totalLinhas,
                'status' => 'mapeamento',
                'mapeamento' => ['ciclo_id' => $this->cicloSelecionadoId]
            ]);

            $this->importacaoAtualId = $importacao->id;
            $this->cabecalhos = $cabecalhosLidos;
            
            $this->reset('arquivo');
            $this->modalUploadAberto = false;

            if ($this->tipoImportacao === 'inscricoes' && in_array(strtolower($extensao), ['csv', 'xlsx', 'xls'])) {
                $this->camposDinamicosDisponiveis = \App\Models\CampoFormulario::where('ciclo_id', $this->cicloSelecionadoId)
                    ->whereNotIn('tipo', ['config', 'html', 'divider', 'media'])
                    ->pluck('label', 'name')
                    ->toArray();
                
                $this->inicializarMapeamentoManualmente();
                $this->modalMapeamentoAberto = true;
            } else {
                $this->iniciarImportacao(); 
            }
        } else {
            $this->dispatch('erro', msg: 'Necessário selecionar um tipo de importação');
        }
    }

    private function inicializarMapeamentoManualmente()
    {
        $this->mapeamento = [];
        foreach ($this->cabecalhos as $index => $coluna) {
            $this->mapeamento[$index] = [
                'coluna_nome' => $coluna, 
                'destino' => 'ignorar', 
                'tipo' => 'texto'
            ];
        }
    }

    public function iniciarImportacao()
    {
        $importacao = Importacao::findOrFail($this->importacaoAtualId);
        
        $mapaFinal = [];
        // Injeta a configuração do checkbox na fila de dados da importação
        $mapaFinal['config_auto_cadastro'] = $this->permitirAutoCadastro;
        $mapaFinal['config_mesclar_duplicadas'] = $this->mesclarDuplicadas;

        foreach($this->mapeamento as $map) {
             if ($map['destino'] !== 'ignorar') {
                 $mapaFinal[$map['coluna_nome']] = [
                      'destino' => $map['destino'],
                      'tipo' => $map['tipo']
                 ];
             }
        }
        
        if ($this->cicloSelecionadoId) {
            $mapaFinal['ciclo_id'] = $this->cicloSelecionadoId; 
        }

        $importacao->update([
            'mapeamento' => $mapaFinal,
            'status' => 'na_fila'
        ]);

        $this->reset(['arquivo', 'importacaoAtualId', 'cabecalhos', 'mapeamento', 'modalMapeamentoAberto', 'camposDinamicosDisponiveis', 'permitirAutoCadastro', 'mesclarDuplicadas']);        $this->dispatch('sucesso', msg: 'Importação enviada para a fila com sucesso!');
        
        dispatch(new \App\Jobs\ProcessarImportacaoUniversalJob($importacao))->afterResponse();
    }

    public function excluirImportacao($id)
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('importacao.acessar'), 403);
        
        $importacao = Importacao::find($id);
        
        if ($importacao) {
            if ($importacao->arquivo_caminho) Storage::disk('local')->delete($importacao->arquivo_caminho);
            if ($importacao->arquivo_gerado_caminho) Storage::disk('local')->delete($importacao->arquivo_gerado_caminho);
            $importacao->delete();
        }

        if ($this->importacaoAtualId == $id) {
            $this->reset(['modalMapeamentoAberto', 'importacaoAtualId', 'cabecalhos', 'mapeamento']);
        }
        if ($this->importacaoDetalhes && $this->importacaoDetalhes->id == $id) {
            $this->reset(['modalDetalhesAberto', 'importacaoDetalhes']);
        }

        $this->dispatch('sucesso', msg: 'Registro e arquivos cancelados/removidos do servidor.');
    }

    public function verDetalhes($id)
    {
        $this->importacaoDetalhes = Importacao::findOrFail($id);
        
        $this->previewCabecalhos = [];
        $this->previewDados = [];
        
        // Se houver um arquivo, lemos as 100 primeiras linhas para o Preview na tela
        if ($this->importacaoDetalhes->arquivo_caminho && Storage::disk('local')->exists($this->importacaoDetalhes->arquivo_caminho)) {
            $caminhoAbsoluto = Storage::disk('local')->path($this->importacaoDetalhes->arquivo_caminho);
            $extensao = pathinfo($caminhoAbsoluto, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($extensao), ['csv', 'xlsx', 'xls'])) {
                try {
                    $reader = \Spatie\SimpleExcel\SimpleExcelReader::create($caminhoAbsoluto);
                    if (strtolower($extensao) === 'csv') {
                        $primeiraLinha = fgets(fopen($caminhoAbsoluto, 'r'));
                        $delimiter = substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',') ? ';' : ',';
                        $reader->useDelimiter($delimiter);
                    }
                    
                    $this->previewCabecalhos = $reader->getHeaders() ?? [];
                    
                    // Prepara o cruzamento de erros
                    $erros = json_decode($this->importacaoDetalhes->erro_mensagem, true) ?? [];
                    $linhasComErro = array_column($erros, 'linha');
                    $mensagensErro = [];
                    $isDev = auth()->user()->hasRole('dev');
                    
                    foreach ($erros as $e) {
                        if (isset($e['linha'])) {
                            $mensagensErro[$e['linha']] = [
                                'tipo' => $e['tipo'] ?? 'Erro',
                                'msg' => $isDev ? ($e['mensagem'] ?? 'Erro') : ($e['amigavel'] ?? $e['mensagem'] ?? 'Erro')
                            ];
                        }
                    }

                    $linhaAtual = 0;
                    // Limita a 100 linhas para não travar o navegador
                    $reader->getRows()->take(100)->each(function(array $rowProperties) use (&$linhaAtual, $linhasComErro, $mensagensErro) {
                        $linhaAtual++;
                        $status = 'Sucesso';
                        $msg = '';
                        $tipoErro = '';
                        
                        if (in_array($linhaAtual, $linhasComErro)) {
                            $status = 'Erro';
                            $tipoErro = $mensagensErro[$linhaAtual]['tipo'] ?? 'Erro';
                            $msg = $mensagensErro[$linhaAtual]['msg'] ?? '';
                        }
                        
                        $this->previewDados[] = [
                            'linha' => $linhaAtual,
                            'status' => $status,
                            'tipo_erro' => $tipoErro,
                            'mensagem' => $msg,
                            'dados' => array_values($rowProperties)
                        ];
                    });
                } catch (\Exception $e) {
                    // Ignora o preview silenciosamente se o arquivo estiver corrompido
                }
            }
        }

        $this->modalDetalhesAberto = true;
    }

    public function baixarErros($id)
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('importacao.acessar'), 403);

        $importacao = Importacao::findOrFail($id);
        $erros = json_decode($importacao->erro_mensagem, true) ?? [];

        if (empty($erros) || !$importacao->arquivo_caminho || !Storage::disk('local')->exists($importacao->arquivo_caminho)) {
            $this->dispatch('erro', msg: 'Não há erros para baixar ou o arquivo original não está mais no servidor.');
            return;
        }

        $linhasComErro = array_column($erros, 'linha');
        $mensagensErro = [];
        $isDev = auth()->user()->hasRole('dev');

        foreach ($erros as $e) {
            if (isset($e['linha'])) {
                $mensagensErro[$e['linha']] = $isDev ? ($e['mensagem'] ?? 'Erro') : ($e['amigavel'] ?? $e['mensagem'] ?? 'Erro');
            }
        }

        $caminhoAbsoluto = Storage::disk('local')->path($importacao->arquivo_caminho);
        $extensao = pathinfo($caminhoAbsoluto, PATHINFO_EXTENSION);
        
        $reader = \Spatie\SimpleExcel\SimpleExcelReader::create($caminhoAbsoluto);
        if (strtolower($extensao) === 'csv') {
            $primeiraLinha = fgets(fopen($caminhoAbsoluto, 'r'));
            $delimiter = substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',') ? ';' : ',';
            $reader->useDelimiter($delimiter);
        }

        // Criamos o cabeçalho novo injetando os avisos do sistema
        $headers = $reader->getHeaders() ?? [];
        array_unshift($headers, 'Motivo_do_Erro_no_Sistema');
        array_unshift($headers, 'Linha_Original');

        $linhasExportar = [];
        $linhasExportar[] = $headers;

        $linhaAtual = 0;
        $reader->getRows()->each(function(array $rowProperties) use (&$linhaAtual, $linhasComErro, $mensagensErro, &$linhasExportar) {
            $linhaAtual++;
            // A mágica: só exportamos as linhas cruzadas com o Log
            if (in_array($linhaAtual, $linhasComErro)) {
                $valores = array_values($rowProperties);
                array_unshift($valores, $mensagensErro[$linhaAtual] ?? 'Erro não especificado');
                array_unshift($valores, $linhaAtual);
                $linhasExportar[] = $valores;
            }
        });

        $nomeArquivo = 'Relatorio_Erros_Importacao_' . $importacao->id . '.csv';
        
        $callback = function() use ($linhasExportar) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); // Previne erro de formatação UTF-8 no Excel
            foreach ($linhasExportar as $linha) {
                fputcsv($file, $linha, ';');
            }
            fclose($file);
        };
        return response()->streamDownload($callback, $nomeArquivo, ['Content-Type' => 'text/csv']);
    }

    public function baixarArquivoOriginal($id)
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('importacao.acessar'), 403);

        $importacao = Importacao::findOrFail($id);

        if ($importacao->arquivo_caminho && Storage::disk('local')->exists($importacao->arquivo_caminho)) {
            $nomeFinal = 'Original_' . ($importacao->arquivo_nome ?? 'planilha.csv');
            return Storage::disk('local')->download($importacao->arquivo_caminho, $nomeFinal);
        }

        $this->dispatch('erro', msg: 'O arquivo original não foi encontrado ou já foi expurgado do servidor.');
    }

    public function solicitarExportacao($tipoDado, $formato = 'xlsx')
    {
        abort_if(!feature('importacao.exportar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('importacao.exportar'), 403);

        $ativos = Importacao::where('user_id', auth()->id())->whereIn('status', ['mapeamento', 'na_fila', 'processando'])->count();
        if ($ativos >= 5) {
            $this->dispatch('sucesso', msg: 'Sua fila está cheia. Aguarde as gerações atuais terminarem.');
            return;
        }

        $exportacao = Importacao::create([
            'user_id' => auth()->id(),
            'tipo' => $tipoDado,
            'operacao' => 'exportacao',
            'formato' => $formato,
            'arquivo_nome' => "Exportacao_" . ucfirst($tipoDado) . ".{$formato}",
            'status' => 'na_fila',
            'total_linhas' => 0, 
        ]);

        dispatch(new \App\Jobs\ProcessarExportacaoUniversalJob($exportacao))->afterResponse();

        $this->dispatch('sucesso', msg: 'Exportação solicitada com sucesso! O sistema está processando em background.');
    }

    public function baixarExportacao($id)
    {
        $log = Importacao::findOrFail($id);
        if ($log->operacao === 'exportacao' && $log->status === 'concluido' && $log->arquivo_gerado_caminho) {
            return Storage::disk('public')->download($log->arquivo_gerado_caminho);
        }
        $this->dispatch('sucesso', msg: 'Arquivo não encontrado ou geração não concluída.');
    }

    public function render()
    {
        $query = Importacao::with('user');
        
        if (!empty($this->filtro_tipo)) $query->where('tipo', $this->filtro_tipo);
        if (!empty($this->filtro_status)) $query->where('status', $this->filtro_status);
        if (!empty($this->filtro_usuario)) $query->where('user_id', $this->filtro_usuario);
        
        if (!empty($this->filtro_data_inicio)) {
            $query->where('created_at', '>=', str_replace('T', ' ', $this->filtro_data_inicio));
        }
        if (!empty($this->filtro_data_fim)) {
            $dataFim = str_replace('T', ' ', $this->filtro_data_fim);
            if (strlen($dataFim) === 10) $dataFim .= ' 23:59:59';
            elseif (strlen($dataFim) === 16) $dataFim .= ':59';
            $query->where('created_at', '<=', $dataFim);
        }

        if ($this->ordenacaoCampo) $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        else $query->orderBy('id', 'desc');

        $usuariosDisponiveis = User::whereIn('id', Importacao::select('user_id')->distinct())
            ->orderBy('name')->pluck('name', 'id');

        return view('livewire.importacao.importacao-manager', [
            'registros' => $query->paginate($this->porPagina),
            'usuariosDisponiveis' => $usuariosDisponiveis
        ])->layout('components.layouts.app', ['title' => 'Gestor de Integrações']);
    }
}