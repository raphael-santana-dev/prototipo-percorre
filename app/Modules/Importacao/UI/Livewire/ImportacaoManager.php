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
use App\Traits\FuzzyMatchingTrait;

class ImportacaoManager extends Component
{
    use WithFileUploads, WithPagination, ComPadraoListagem, FuzzyMatchingTrait;

    public array $breadcrumbs = [];

    public $modalUploadAberto = false;
    public $modalMapeamentoAberto = false;
    public $modalDetalhesAberto = false;
    public $modalReprocessarAberto = false;
    public $modalMonitoramentoAberto = false;
    public $importacaoReprocessarId = null;

    public $arquivo;
    public $tipoImportacao = 'inscricoes'; // Fixo para Inscrições
    public $cicloSelecionadoId = null;
    public $ciclosDisponiveis = [];

    public $importacaoAtualId = null;
    public $importacaoDetalhes = null;
    public $permitirAutoCadastro = false;
    public $mesclarDuplicadas = false;
    public $refazerMapeamento = false;

    public $cabecalhos = [];
    public $mapeamento = [];
    
    public $importacaoMonitoramento = null;
    
    public $filtro_status = '';
    public $filtro_usuario = '';
    public $filtro_data_inicio = '';
    public $filtro_data_fim = '';

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
        'pontuacao_total' => 'Pontuação Total',
        'posicao_ranking' => 'Posição no Ranking',
        'etapa_atual' => 'Progresso (Etapa Atual)',
        'regiao' => 'Região (Ex.: Norte, Sul, Leste, Oeste)',
        'created_at' => 'Data da Criaçãpo',
        'updated_at' => 'Última Atualização (updated_at)',
        'data_inscricao' => 'Data da Inscrição',
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
        if (in_array($nomePropriedade, ['filtro_status', 'filtro_usuario', 'filtro_data_inicio', 'filtro_data_fim'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_status', 'filtro_usuario', 'filtro_data_inicio', 'filtro_data_fim']);
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

    public function baixarTemplate()
    {
        $cabecalho = ['Nome', 'E-mail', 'CPF', 'Celular', 'Data de Nascimento', 'Estado', 'Unidade', 'Curso', 'Turno'];
        $exemplo = ['Maria Oliveira', 'maria@email.com', '123.456.789-00', '11999999999', '15/05/2000', 'SP', 'Unidade Paulista', 'Design Gráfico', 'Noturno'];
        
        $callback = function() use ($cabecalho, $exemplo) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // Força UTF-8 BOM para Excel
            fputcsv($file, $cabecalho, ';');
            fputcsv($file, $exemplo, ';');
            fclose($file);
        };
        return response()->streamDownload($callback, 'modelo_importacao_inscricoes.csv', ['Content-Type' => 'text/csv']);
    }

    public function abrirModalReprocessar($id)
    {
        $this->reset('refazerMapeamento');
        $this->importacaoReprocessarId = $id;
        $this->modalReprocessarAberto = true;
    }

    private function carregarCamposDinamicos()
    {
        $camposDoCiclo = \App\Models\CampoFormulario::where('ciclo_id', $this->cicloSelecionadoId)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media'])
            ->get();
        
        $this->camposDinamicosDisponiveis = [];
        foreach ($camposDoCiclo as $campo) {
            if (str_contains(strtolower($campo->name), 'form_config')) continue;
            
            if ($campo->tipo === 'social') {
                $config = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
                $redes = $config['redes_permitidas'] ?? [];
                foreach ($redes as $rede) {
                    $this->camposDinamicosDisponiveis["{$campo->name}.{$rede}"] = "{$campo->label} (" . ucfirst($rede) . ")";
                }
            } else {
                $this->camposDinamicosDisponiveis[$campo->name] = $campo->label;
            }
        }
    }

    public function reprocessar($modo)
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('importacao.acessar'), 403);

        $importacao = Importacao::findOrFail($this->importacaoReprocessarId);
        $mapa = $importacao->mapeamento;

        if ($modo === 'falhas') {
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
            $mapa['linhas_reprocessar'] = array_values(array_unique($linhasComErro));
        } else {
            if (isset($mapa['linhas_reprocessar'])) {
                unset($mapa['linhas_reprocessar']);
            }
        }

        if ($this->refazerMapeamento) {
            $importacao->update([
                'mapeamento' => $mapa,
                'status' => 'mapeamento',
            ]);

            $caminhoAbsoluto = Storage::disk('local')->path($importacao->arquivo_caminho);
            $formato = $importacao->formato;
            
            $cabecalhosLidos = [];
            if (in_array(strtolower($formato), ['csv', 'xlsx', 'xls'])) {
                if (strtolower($formato) === 'csv') {
                    $primeiraLinha = fgets(fopen($caminhoAbsoluto, 'r'));
                    $delimiter = substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',') ? ';' : ',';
                    $reader = \Spatie\SimpleExcel\SimpleExcelReader::create($caminhoAbsoluto)->useDelimiter($delimiter);
                } else {
                    $reader = \Spatie\SimpleExcel\SimpleExcelReader::create($caminhoAbsoluto);
                }

                $headers = $reader->getHeaders() ?? [];
                foreach ($headers as $h) {
                    $cabecalhosLidos[] = mb_convert_encoding(trim($h), 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
                }
            }
            
            $this->cabecalhos = $cabecalhosLidos;
            $this->importacaoAtualId = $importacao->id;
            $this->cicloSelecionadoId = $mapa['ciclo_id'] ?? null;
            $this->permitirAutoCadastro = filter_var($mapa['config_auto_cadastro'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $this->mesclarDuplicadas = filter_var($mapa['config_mesclar_duplicadas'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $this->carregarCamposDinamicos();

            $this->mapeamento = [];
            foreach ($this->cabecalhos as $index => $coluna) {
                $destino = 'ignorar';
                $tipo = 'texto';
                
                if (isset($mapa[$coluna])) {
                    $destino = $mapa[$coluna]['destino'] ?? 'ignorar';
                    $tipo = $mapa[$coluna]['tipo'] ?? 'texto';
                }
                
                $this->mapeamento[$index] = ['coluna_nome' => $coluna, 'destino' => $destino, 'tipo' => $tipo];
            }

            $this->reset(['modalReprocessarAberto', 'importacaoReprocessarId', 'modalDetalhesAberto', 'importacaoDetalhes', 'refazerMapeamento']);
            $this->modalMapeamentoAberto = true;

        } else {
            $importacao->update([
                'status' => 'na_fila',
                'linhas_processadas' => 0,
                'erro_mensagem' => null,
                'mapeamento' => $mapa
            ]);

            $this->reset(['modalReprocessarAberto', 'importacaoReprocessarId', 'modalDetalhesAberto', 'importacaoDetalhes', 'refazerMapeamento']);
            
            $this->importacaoAtualId = $importacao->id;
            $this->modalMonitoramentoAberto = true;
            $this->dispatch('sucesso', msg: 'A importação foi enviada para processamento!');

            dispatch(new \App\Jobs\ProcessarImportacaoUniversalJob($importacao))->afterResponse();
        }
    }

    public function abrirModalUpload()
    {
        $this->reset(['arquivo', 'cicloSelecionadoId', 'camposDinamicosDisponiveis', 'permitirAutoCadastro', 'mesclarDuplicadas']);
        $this->modalUploadAberto = true;
    }

    public function processarUpload()
    {
        // 1. SEGURANÇA MÁXIMA: Validação restrita de formato e tamanho
        $this->validate([
            'arquivo' => 'required|file|mimes:csv,xlsx,xls,txt|max:51200',
            'cicloSelecionadoId' => 'required|exists:ciclos,id'
        ], [
            'arquivo.mimes' => 'Apenas arquivos CSV ou Excel (.xlsx, .xls) são permitidos por segurança.',
            'cicloSelecionadoId.required' => 'Obrigatório selecionar o ciclo de inscrição.'
        ]);

        $ativos = Importacao::where('user_id', auth()->id())->whereIn('status', ['mapeamento', 'na_fila', 'processando'])->count();
        if ($ativos >= 5) {
            $this->addError('arquivo', 'Fila cheia! Aguarde a conclusão das importações anteriores.');
            return;
        }

        $extensao = $this->arquivo->getClientOriginalExtension();
        // Fallback: Laravel às vezes detecta CSV como txt. Forçamos o tratamento visual.
        $formatoFinal = in_array(strtolower($extensao), ['txt', 'csv']) ? 'csv' : strtolower($extensao);

        $caminho = $this->arquivo->store('importacoes', 'local'); 
        $caminhoAbsoluto = Storage::disk('local')->path($caminho);
        
        $totalLinhas = 0;
        $cabecalhosLidos = [];
        
        if ($formatoFinal === 'csv') {
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

        $importacao = Importacao::create([
            'user_id' => auth()->id(),
            'tipo' => 'inscricoes',
            'operacao' => 'importacao',
            'formato' => $formatoFinal,
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

        $this->carregarCamposDinamicos();
        $this->inicializarMapeamentoManualmente();
        $this->modalMapeamentoAberto = true;
    }

    private function inicializarMapeamentoManualmente()
    {
        $this->mapeamento = [];

        $todosDestinos = [];
        foreach ($this->opcoesMapeamento as $chave => $label) {
            $todosDestinos[$chave] = $label;
        }
        foreach ($this->camposDinamicosDisponiveis as $name => $label) {
            $todosDestinos["dinamico:{$name}"] = $label;
        }

        foreach ($this->cabecalhos as $index => $colunaPlanilha) {
            $melhorDestino = 'ignorar';
            $maiorScore = 0;
            $tipoSugerido = 'texto';

            foreach ($todosDestinos as $chaveDestino => $labelDestino) {
                $scoreLabel = $this->calcularCompatibilidade($colunaPlanilha, $labelDestino);
                $chaveLimpa = str_replace('dinamico:', '', $chaveDestino);
                $scoreChave = $this->calcularCompatibilidade($colunaPlanilha, str_replace('_', ' ', $chaveLimpa));
                $scoreFinal = max($scoreLabel, $scoreChave);

                if ($scoreFinal >= 50 && $scoreFinal > $maiorScore) {
                    $maiorScore = $scoreFinal;
                    $melhorDestino = $chaveDestino;
                }
            }

            $colunaLower = strtolower($colunaPlanilha);
            
            if (str_contains($colunaLower, 'submission started') || str_contains($colunaLower, 'created') || str_contains($colunaLower, 'criado em')) {
                $melhorDestino = 'data_inscricao';
                $tipoSugerido = 'data';
            } elseif (str_contains($colunaLower, 'last updated') || str_contains($colunaLower, 'updated') || str_contains($colunaLower, 'atualizado em')) {
                $melhorDestino = 'updated_at';
                $tipoSugerido = 'data';
            } elseif (str_contains($colunaLower, 'data') || str_contains($colunaLower, 'nascimento')) {
                $tipoSugerido = 'data';
            } elseif (str_contains($colunaLower, 'renda') || str_contains($colunaLower, 'valor') || str_contains($colunaLower, 'salario')) {
                $tipoSugerido = 'monetario';
            } elseif (str_contains($colunaLower, 'possui') || str_contains($colunaLower, 'bolsista')) {
                $tipoSugerido = 'booleano';
            }

            $this->mapeamento[$index] = [
                'coluna_nome' => $colunaPlanilha, 
                'destino' => $melhorDestino, 
                'tipo' => $tipoSugerido
            ];
        }
    }

    public function iniciarImportacao()
    {
        $importacao = Importacao::findOrFail($this->importacaoAtualId);
        
        $mapaFinal = [];
        $mapaFinal['config_auto_cadastro'] = (bool) $this->permitirAutoCadastro;
        $mapaFinal['config_mesclar_duplicadas'] = (bool) $this->mesclarDuplicadas;

        if (isset($importacao->mapeamento['linhas_reprocessar'])) {
            $mapaFinal['linhas_reprocessar'] = $importacao->mapeamento['linhas_reprocessar'];
        }

        foreach($this->cabecalhos as $index => $colunaNome) {
            if (isset($this->mapeamento[$index])) {
                $destino = $this->mapeamento[$index]['destino'] ?? 'ignorar';
                $tipo = $this->mapeamento[$index]['tipo'] ?? 'texto';

                if ($destino !== 'ignorar') {
                    $mapaFinal[$colunaNome] = ['destino' => $destino, 'tipo' => $tipo];
                }
            }
        }
        
        if ($this->cicloSelecionadoId) {
            $mapaFinal['ciclo_id'] = $this->cicloSelecionadoId; 
        }

        $importacao->update([
            'mapeamento' => $mapaFinal,
            'status' => 'na_fila'
        ]);

        $this->modalMapeamentoAberto = false;
        $this->modalMonitoramentoAberto = true;

        $this->reset(['camposDinamicosDisponiveis', 'mapeamento', 'cabecalhos', 'arquivo']);
        
        dispatch(new \App\Jobs\ProcessarImportacaoUniversalJob($importacao));
    }

    public function monitorarProgresso()
    {
        if ($this->importacaoAtualId) {
            $this->importacaoMonitoramento = Importacao::find($this->importacaoAtualId);
            
            if ($this->importacaoMonitoramento && in_array($this->importacaoMonitoramento->status, ['concluido', 'erro', 'erro_parcial'])) {
                $this->modalMonitoramentoAberto = false;
                $this->verDetalhes($this->importacaoAtualId); 
            }
        }
    }

    public function fecharMonitoramento()
    {
        $this->modalMonitoramentoAberto = false;
        $this->importacaoMonitoramento = null;
        $this->importacaoAtualId = null;
    }

    public function cancelarImportacaoListagem($id, $apagarDados = false)
    {
        $this->importacaoAtualId = $id;
        $this->cancelarImportacao($apagarDados);
    }

    public function cancelarImportacao($apagarDados = false)
    {
        if (!$this->importacaoAtualId) return;

        $importacao = Importacao::find($this->importacaoAtualId);
        if (!$importacao) return;

        $erros = json_decode($importacao->erro_mensagem, true) ?? [];
        $erros[] = [
            'linha' => '-',
            'tipo' => 'Cancelamento Manual',
            'mensagem' => 'A importação foi cancelada pelo usuário através do painel.',
            'amigavel' => 'Cancelado pelo usuário.'
        ];

        $importacao->update([
            'status' => 'erro', 
            'erro_mensagem' => json_encode($erros)
        ]);

        if ($apagarDados) {
            \App\Models\Inscricao::where('criado_por', $importacao->user_id)
                ->where('created_at', '>=', $importacao->created_at)
                ->delete();

            if ($importacao->arquivo_caminho && \Illuminate\Support\Facades\Storage::disk('local')->exists($importacao->arquivo_caminho)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($importacao->arquivo_caminho);
            }

            $this->dispatch('sucesso', msg: 'Importação interrompida e os dados foram revertidos com sucesso.');
        } else {
            $this->dispatch('sucesso', msg: 'Sinal de cancelamento enviado! O processamento parará no lote atual.');
        }

        $this->fecharMonitoramento();
        $this->resetPage();
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
            $this->reset(['modalMapeamentoAberto', 'importacaoAtualId', 'cabecalhos', 'mapeamento', 'modalMonitoramentoAberto']);
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

        $headers = $reader->getHeaders() ?? [];
        array_unshift($headers, 'Motivo_do_Erro_no_Sistema');
        array_unshift($headers, 'Linha_Original');

        $linhasExportar = [];
        $linhasExportar[] = $headers;

        $linhaAtual = 0;
        $reader->getRows()->each(function(array $rowProperties) use (&$linhaAtual, $linhasComErro, $mensagensErro, &$linhasExportar) {
            $linhaAtual++;
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
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); 
            foreach ($linhasExportar as $linha) {
                $linhaSanitizada = array_map(function ($valor) {
                    $valorStr = (string) $valor;
                    if (preg_match('/^[\=\+\-\@\t\r]/', $valorStr)) { return "'" . $valorStr; }
                    return $valorStr;
                }, $linha);
                fputcsv($file, $linhaSanitizada, ';');
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

        $this->dispatch('erro', msg: 'O arquivo original não foi encontrado no servidor.');
    }

    public function solicitarExportacao()
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
            'tipo' => 'inscricoes',
            'operacao' => 'exportacao',
            'formato' => 'xlsx',
            'arquivo_nome' => "Exportacao_Inscricoes.xlsx",
            'status' => 'na_fila',
            'total_linhas' => 0, 
        ]);

        dispatch(new \App\Jobs\ProcessarExportacaoUniversalJob($exportacao))->afterResponse();

        $this->dispatch('sucesso', msg: 'Exportação da base completa de Inscrições solicitada! O sistema está processando em background.');
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
        $query = Importacao::with('user')->where('tipo', 'inscricoes');
        
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
        ])->layout('components.layouts.app', ['title' => 'Gestor de Integrações (Inscrições)']);
    }
}