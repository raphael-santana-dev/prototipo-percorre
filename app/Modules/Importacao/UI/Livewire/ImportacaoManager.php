<?php

namespace App\Modules\Importacao\UI\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Importacao;
use App\Models\Ciclo;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;

class ImportacaoManager extends Component
{
    use WithFileUploads, WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];

    // Propriedades do Upload e Mapeamento
    public $modalUploadAberto = false;
    public $modalMapeamentoAberto = false;
    public $modalErroAberto = false;

    public $arquivo;
    public $tipoImportacao = 'inscricoes'; // inscricoes, usuarios, campos
    public $cicloSelecionadoId = null;
    public $ciclosDisponiveis = [];

    public $importacaoAtualId = null;
    public $cabecalhos = [];
    public $mapeamento = [];
    
    public $mensagemErroAtual = '';
    public $arquivoErroAtual = '';

    // Opções disponíveis para o usuário mapear (De/Para)
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
        'bairro' => 'Bairro',
        'cidade' => 'Cidade',
        'estado' => 'Estado (UF)',
        'unidade_id' => 'Sede/Unidade (Nome da Unidade)',
        'curso_id' => 'Curso (Nome do Curso)',
        'turno_id' => 'Turno (Nome do Turno)',
        'possui_deficiencia' => 'Possui Deficiência?',
        'natureza_deficiencia' => 'Natureza da Deficiência',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Você não possui permissão para gerenciar importações.');
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->ciclosDisponiveis = Ciclo::orderBy('nome', 'asc')->get();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#', 'sortable' => false, 'class' => 'w-16'],
            ['key' => 'arquivo', 'label' => 'Arquivo / Tipo', 'sortable' => false],
            ['key' => 'progresso', 'label' => 'Status e Progresso', 'sortable' => false, 'class' => 'w-64'],
            ['key' => 'data', 'label' => 'Data de Envio', 'sortable' => false],
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'w-24 text-right'],
        ];
    }

    // --- TEMPLATES PARA DOWNLOAD ---
    public function baixarTemplate($tipo)
    {
        if ($tipo === 'usuarios') {
            $cabecalho = ['nome', 'email', 'cpf', 'senha', 'role', 'permissoes'];
            $exemplo = ['João Silva', 'joao@email.com', '123.456.789-00', 'senha123', 'estudante', 'ver_aulas, editar_perfil'];
            return $this->gerarCsv('modelo_importacao_usuarios.csv', [$cabecalho, $exemplo]);
        }

        if ($tipo === 'campos') {
            $cabecalho = ['etapa', 'ordem', 'label', 'name', 'tipo', 'largura', 'subtipo', 'tamanho_min', 'tamanho_max', 'regex_mascara', 'opcoes', 'obrigatorio', 'regras_validacao', 'depende_de', 'depende_operador', 'depende_valor'];
            $exemplo = ['1', '1', 'Qual sua Renda?', 'renda_familiar', 'select', '12', '', '', '', '', 'bd:rendas', '1', 'required', '', '', ''];
            return $this->gerarCsv('modelo_importacao_campos.csv', [$cabecalho, $exemplo]);
        }
    }

    private function gerarCsv($nomeArquivo, $dados)
    {
        $callback = function() use ($dados) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); // Formato UTF-8 com BOM para Excel ler acentos
            foreach ($dados as $linha) {
                fputcsv($file, $linha, ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $nomeArquivo, ['Content-Type' => 'text/csv']);
    }

    // --- FLUXO DE UPLOAD E MAPEAMENTO ---
    public function abrirModalUpload()
    {
        $this->reset(['arquivo', 'tipoImportacao', 'cicloSelecionadoId']);
        $this->modalUploadAberto = true;
    }

    public function processarUpload()
    {
        $regras = ['arquivo' => 'required|mimes:csv,xlsx,xls,json,xml|max:51200']; // Max 50MB
        if ($this->tipoImportacao === 'campos' || $this->tipoImportacao === 'inscricoes') {
            $regras['cicloSelecionadoId'] = 'required';
        }
        $this->validate($regras, ['cicloSelecionadoId.required' => 'Obrigatório selecionar o ciclo para este tipo de importação.']);

        // Trava para evitar sobrecarga (Max 5 importações simultâneas pendentes por usuário)
        $ativos = Importacao::where('user_id', auth()->id())->whereIn('status', ['mapeamento', 'na_fila', 'processando'])->count();
        if ($ativos >= 5) {
            $this->addError('arquivo', 'Fila cheia! Aguarde a conclusão das importações anteriores.');
            return;
        }

        $extensao = $this->arquivo->getClientOriginalExtension();
        $caminho = $this->arquivo->store('importacoes', 'local'); 
        $caminhoAbsoluto = Storage::disk('local')->path($caminho);
        
        $totalLinhas = 0;
        
        // Se for Excel/CSV, usamos o Spatie Reader para contar e pegar cabeçalhos
        if (in_array(strtolower($extensao), ['csv', 'xlsx', 'xls'])) {
            $reader = SimpleExcelReader::create($caminhoAbsoluto);
            if (strtolower($extensao) === 'csv') {
                $cabecalhoRaw = file_get_contents($caminhoAbsoluto, false, null, 0, 250);
                $reader->useDelimiter(strpos($cabecalhoRaw, ';') !== false ? ';' : ',');
            }
            $this->cabecalhos = $reader->getHeaders() ?? [];
            $totalLinhas = $reader->getRows()->count();
        } else {
            // Lógica para JSON/XML será implementada no Motor (Etapa 4)
            $this->cabecalhos = ['Dados Brutos (Mapeamento Automático)'];
            $totalLinhas = 1; // Temporário até processar
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
        $this->modalUploadAberto = false;

        // Se for inscrição, obriga a mapear colunas. Senão, vai direto pra fila.
        if ($this->tipoImportacao === 'inscricoes' && in_array(strtolower($extensao), ['csv', 'xlsx', 'xls'])) {
            $this->gerarAutoMapeamento();
            $this->modalMapeamentoAberto = true;
        } else {
            $this->iniciarImportacao(); // Envia para fila direto
        }
    }

    private function gerarAutoMapeamento()
    {
        $opcoesLabelMap = [];
        foreach($this->opcoesMapeamento as $chave => $label) {
            $opcoesLabelMap[Str::slug($label)] = $chave;
        }

        $this->mapeamento = [];
        foreach ($this->cabecalhos as $coluna) {
            $slugColuna = Str::slug($coluna);
            $destino = 'dados_dinamicos'; 
            $tipo = 'texto';

            if (isset($this->opcoesMapeamento[$slugColuna])) $destino = $slugColuna;
            elseif (isset($opcoesLabelMap[$slugColuna])) $destino = $opcoesLabelMap[$slugColuna];
            else {
                if ($slugColuna === 'cpf') $destino = 'cpf';
                if (str_contains($slugColuna, 'e-mail') || str_contains($slugColuna, 'email')) $destino = 'email';
                if (str_contains($slugColuna, 'celular') || str_contains($slugColuna, 'telefone')) $destino = 'celular';
            }

            if (str_contains($slugColuna, 'data') && str_contains($slugColuna, 'nascimento')) $tipo = 'data';
            elseif (str_contains($slugColuna, 'data') || str_contains($slugColuna, 'hora')) $tipo = 'data_hora';

            $this->mapeamento[$coluna] = ['destino' => $destino, 'tipo' => $tipo];
        }
    }

    public function iniciarImportacao()
    {
        $importacao = Importacao::findOrFail($this->importacaoAtualId);
        
        $mapaFinal = $this->mapeamento;
        if ($this->cicloSelecionadoId) {
            $mapaFinal['ciclo_id'] = $this->cicloSelecionadoId; 
        }

        $importacao->update([
            'mapeamento' => $mapaFinal,
            'status' => 'na_fila'
        ]);

        $this->reset(['arquivo', 'importacaoAtualId', 'cabecalhos', 'mapeamento', 'modalMapeamentoAberto']);
        $this->dispatch('sucesso', msg: 'Importação adicionada à fila! O processamento ocorrerá em segundo plano.');
        
        // Na etapa 4 usaremos o Job que vamos criar!
        \App\Jobs\ProcessarImportacaoUniversalJob::dispatch($importacao);
    }

    public function excluirImportacao($id)
    {
        $importacao = Importacao::findOrFail($id);
        if ($importacao->arquivo_caminho) Storage::disk('local')->delete($importacao->arquivo_caminho);
        if ($importacao->arquivo_gerado_caminho) Storage::disk('local')->delete($importacao->arquivo_gerado_caminho);
        $importacao->delete();
        $this->dispatch('sucesso', msg: 'Registro e arquivos removidos do servidor.');
    }

    public function verErro($id)
    {
        $importacao = Importacao::findOrFail($id);
        $this->mensagemErroAtual = $importacao->erro_mensagem;
        $this->arquivoErroAtual = $importacao->arquivo_nome;
        $this->modalErroAberto = true;
    }

    public function solicitarExportacao($tipoDado, $formato = 'xlsx')
    {
        // Trava de prevenção de sobrecarga (limite de 5 itens simultâneos)
        $ativos = Importacao::where('user_id', auth()->id())->whereIn('status', ['mapeamento', 'na_fila', 'processando'])->count();
        if ($ativos >= 5) {
            $this->dispatch('sucesso', msg: 'Sua fila está cheia. Aguarde as gerações atuais terminarem.');
            return;
        }

        // Cria o log de Exportação
        $exportacao = Importacao::create([
            'user_id' => auth()->id(),
            'tipo' => $tipoDado,
            'operacao' => 'exportacao',
            'formato' => $formato,
            'arquivo_nome' => "Exportacao_" . ucfirst($tipoDado) . ".{$formato}",
            'status' => 'na_fila',
            'total_linhas' => 0, // Será calculado no Job
        ]);

        // Dispara o Job de Exportação em background
        \App\Jobs\ProcessarExportacaoUniversalJob::dispatch($exportacao);

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
        
        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        // Alteramos para buscar a view na nova pasta
        return view('livewire.importacao.importacao-manager', [
            'registros' => $query->paginate($this->porPagina)
        ])->layout('components.layouts.app', ['title' => 'Gestor de Importações']);
    }
}