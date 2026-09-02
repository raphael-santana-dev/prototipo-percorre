<?php

namespace App\Modules\Matricula\UI\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use App\Modules\Matricula\Services\AiValidationService;
use Illuminate\Support\Facades\Storage;

#[Layout('components.layouts.public')] // Usa o layout sem sidebar de admin
#[Title('Portal de Matrícula')]
class PortalMatricula extends Component
{
    use WithFileUploads;

    public $token;
    public $inscricao;
    public $documentosExigidos = [];
    public $uploads = []; // Arquivos temporários do Livewire
    public $arquivosEnviados = []; // Controle dos status dos documentos

    public $concluido = false;

    public function mount($token)
    {
        // Busca a inscrição pelo token único
        $this->inscricao = Inscricao::with(['curso', 'unidade', 'ciclo'])
            ->where('token_matricula', $token)
            ->firstOrFail();

        // Busca o que é exigido para o ciclo do aluno
        $this->documentosExigidos = DocumentoExigido::where('ciclo_id', $this->inscricao->ciclo_id)->get();

        $this->carregarStatusArquivos();
    }

    public function carregarStatusArquivos()
    {
        $documentosSalvos = DocumentoMatricula::where('inscricao_id', $this->inscricao->id)->get()->keyBy('documento_exigido_id');

        foreach ($this->documentosExigidos as $doc) {
            if ($documentosSalvos->has($doc->id)) {
                $salvo = $documentosSalvos->get($doc->id);
                $this->arquivosEnviados[$doc->id] = [
                    'id' => $salvo->id,
                    'status' => $salvo->status_analise, // valido_ia, invalido_ia, analise_manual
                    'tentativas' => $salvo->tentativas_ia,
                    'motivo_rejeicao' => $salvo->log_ia['motivo_rejeicao'] ?? ''
                ];
            } else {
                $this->arquivosEnviados[$doc->id] = [
                    'status' => 'pendente',
                    'tentativas' => 0,
                    'motivo_rejeicao' => ''
                ];
            }
        }
    }

    public function updatedUploads($value, $documentoExigidoId)
    {
        $this->validate([
            "uploads.{$documentoExigidoId}" => 'image|max:10240', // Apenas imagens, máx 10MB
        ]);

        $file = $this->uploads[$documentoExigidoId];
        $documentoModel = DocumentoExigido::find($documentoExigidoId);

        // Salva o arquivo no disco local/privado
        $caminho = $file->store("matriculas/{$this->inscricao->id}");
        
        // Busca ou cria o registro do cofre
        $docMatricula = DocumentoMatricula::firstOrNew([
            'inscricao_id' => $this->inscricao->id,
            'documento_exigido_id' => $documentoExigidoId,
        ]);

        $docMatricula->arquivo_caminho = $caminho;
        $docMatricula->arquivo_extensao = $file->getClientOriginalExtension();
        $docMatricula->tentativas_ia = $docMatricula->tentativas_ia + 1;
        $docMatricula->save();

        // Aciona a IA
        $resultadoIa = AiValidationService::validarDocumento($this->inscricao, $documentoModel, $caminho);

        if ($resultadoIa['valido']) {
            $docMatricula->status_analise = 'valido_ia';
            $docMatricula->log_ia = $resultadoIa['raw'] ?? [];
        } else {
            // Se falhou e atingiu 3 tentativas, joga pra análise humana
            if ($docMatricula->tentativas_ia >= 3) {
                $docMatricula->status_analise = 'analise_manual';
            } else {
                $docMatricula->status_analise = 'invalido_ia';
            }
            $docMatricula->log_ia = ['motivo_rejeicao' => $resultadoIa['motivo_rejeicao'], 'raw' => $resultadoIa['raw'] ?? []];
        }

        $docMatricula->save();
        $this->carregarStatusArquivos(); // Atualiza a tela
    }

    public function finalizarMatricula()
    {
        // Confere se todos os documentos OBRIGATÓRIOS foram validados ou estão para análise manual
        foreach ($this->documentosExigidos as $doc) {
            if ($doc->is_obrigatorio) {
                $status = $this->arquivosEnviados[$doc->id]['status'] ?? 'pendente';
                if (in_array($status, ['pendente', 'invalido_ia'])) {
                    $this->dispatch('erro', msg: 'Você possui documentos pendentes ou inválidos. Faça o upload corretamente.');
                    return;
                }
            }
        }

        // Marca a etapa como concluída
        $this->inscricao->update(['etapa_atual' => 'Análise de Matrícula']);
        $this->concluido = true;
    }

    public function render()
    {
        return view('livewire.matricula.portal-matricula');
    }
}