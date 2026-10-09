<?php

namespace App\Modules\Student\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use App\Models\Inscricao;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use App\Modules\Matricula\Services\AiValidationService;
use Illuminate\Support\Facades\Storage;

#[Layout('components.layouts.student-app')]
#[Title('Envio de Documentos - Portal do Estudante')]
class DocumentManager extends Component
{
    use WithFileUploads;

    public array $uploads = [];
    public array $uploadsLote = [];
    public array $arquivosEnviados = [];
    public bool $documentacaoConcluida = false;

    public function mount()
    {
        $this->carregarStatusArquivos();
    }

    #[Computed]
    public function inscricaoAtual()
    {
        return Inscricao::with(['curso', 'unidade', 'ciclo'])
            ->where('student_id', auth('student')->id())
            ->latest('id')
            ->first();
    }

    #[Computed]
    public function documentosExigidos()
    {
        if (!$this->inscricaoAtual) return collect();
        return DocumentoExigido::where('ciclo_id', $this->inscricaoAtual->ciclo_id)->get();
    }

    public function carregarStatusArquivos()
    {
        $inscricao = $this->inscricaoAtual;
        if (!$inscricao) return;

        if ($inscricao->etapa_atual >= 2) {
            $this->documentacaoConcluida = true;
        }

        $documentosSalvos = DocumentoMatricula::where('inscricao_id', $inscricao->id)
            ->get()
            ->keyBy('documento_exigido_id');

        $docsExigidos = $this->documentosExigidos;

        foreach ($docsExigidos as $doc) {
            if ($documentosSalvos->has($doc->id)) {
                $salvo = $documentosSalvos->get($doc->id);
                $this->arquivosEnviados[$doc->id] = [
                    'id' => $salvo->id,
                    'status' => $salvo->status_analise,
                    'tentativas' => $salvo->tentativas_ia,
                    'motivo_rejeicao' => $salvo->log_ia['motivo_rejeicao'] ?? '',
                    'motivo_rejeicao_humana' => $salvo->log_ia['motivo_rejeicao_humana'] ?? ''
                ];
            } else {
                $this->arquivosEnviados[$doc->id] = [
                    'status' => 'pendente',
                    'tentativas' => 0,
                    'motivo_rejeicao' => '',
                    'motivo_rejeicao_humana' => ''
                ];
            }
        }
    }

    public function updatedUploads($value, $documentoExigidoId)
    {
        $this->validate([
            "uploads.{$documentoExigidoId}" => 'required|file|mimes:jpeg,png,jpg,webp|max:10240'
        ], [
            "uploads.{$documentoExigidoId}.mimes" => 'Apenas arquivos JPEG, PNG e WebP são aceitos.',
            "uploads.{$documentoExigidoId}.max" => 'O tamanho máximo do documento é de 10MB.'
        ]);

        $file = $this->uploads[$documentoExigidoId];
        $documentoModel = DocumentoExigido::findOrFail($documentoExigidoId);
        $inscricao = $this->inscricaoAtual;

        $docMatricula = DocumentoMatricula::firstOrNew([
            'inscricao_id' => $inscricao->id,
            'documento_exigido_id' => $documentoExigidoId,
        ]);

        if ($docMatricula->arquivo_caminho && Storage::disk('local')->exists($docMatricula->arquivo_caminho)) {
            Storage::disk('local')->delete($docMatricula->arquivo_caminho);
        }

        $caminho = $file->store("matriculas/{$inscricao->id}");

        $docMatricula->arquivo_caminho = $caminho;
        $docMatricula->arquivo_extensao = $file->getClientOriginalExtension();
        $docMatricula->tentativas_ia = $docMatricula->tentativas_ia + 1;
        $docMatricula->save();

        $resultadoIa = AiValidationService::validarDocumento($inscricao, $documentoModel, $caminho);

        if ($resultadoIa['valido']) {
            $docMatricula->status_analise = 'valido_ia';
            $docMatricula->log_ia = $resultadoIa['raw'] ?? [];
        } else {
            if ($docMatricula->tentativas_ia >= 3) {
                $docMatricula->status_analise = 'analise_manual';
            } else {
                $docMatricula->status_analise = 'invalido_ia';
            }
            $docMatricula->log_ia = [
                'motivo_rejeicao' => $resultadoIa['motivo_rejeicao'],
                'raw' => $resultadoIa['raw'] ?? []
            ];
        }

        $docMatricula->save();
        $this->carregarStatusArquivos();
        
        $this->dispatch('analise-concluida', docId: $documentoExigidoId);
    }

    public function updatedUploadsLote()
    {
        $this->validate([
            'uploadsLote.*' => 'required|file|mimes:jpeg,png,jpg,webp|max:10240'
        ]);

        $sucessos = 0;
        $falhas = 0;
        $inscricao = $this->inscricaoAtual;

        foreach ($this->uploadsLote as $file) {
            $caminhoTemp = $file->store("matriculas/{$inscricao->id}/temp");
            
            $resultadoIa = AiValidationService::classificarDocumentoLote($inscricao, $this->documentosExigidos, $caminhoTemp);

            if ($resultadoIa['documento_id'] > 0) {
                $docId = $resultadoIa['documento_id'];
                
                $caminhoFinal = str_replace('/temp/', '/', $caminhoTemp);
                Storage::move($caminhoTemp, $caminhoFinal);

                $docMatricula = DocumentoMatricula::firstOrNew([
                    'inscricao_id' => $inscricao->id,
                    'documento_exigido_id' => $docId,
                ]);

                if ($docMatricula->arquivo_caminho && Storage::exists($docMatricula->arquivo_caminho)) {
                    Storage::delete($docMatricula->arquivo_caminho);
                }

                $docMatricula->arquivo_caminho = $caminhoFinal;
                $docMatricula->arquivo_extensao = $file->getClientOriginalExtension();
                $docMatricula->tentativas_ia = $docMatricula->tentativas_ia + 1;
                
                if ($resultadoIa['valido']) {
                    $docMatricula->status_analise = 'valido_ia';
                    $docMatricula->log_ia = $resultadoIa['raw'] ?? [];
                    $sucessos++;
                } else {
                    if ($docMatricula->tentativas_ia >= 3) {
                        $docMatricula->status_analise = 'analise_manual';
                    } else {
                        $docMatricula->status_analise = 'invalido_ia';
                    }
                    $docMatricula->log_ia = ['motivo_rejeicao' => $resultadoIa['motivo_rejeicao'], 'raw' => $resultadoIa['raw'] ?? []];
                    $falhas++;
                }
                $docMatricula->save();
            } else {
                Storage::delete($caminhoTemp); 
                $falhas++;
            }
        }

        $this->uploadsLote = [];
        $this->carregarStatusArquivos();
        
        $this->dispatch('lote-concluido', msg: "Processamento concluído: {$sucessos} válidos e alocados. {$falhas} inválidos.");
    }

    public function finalizarMatricula()
    {
        foreach ($this->documentosExigidos as $doc) {
            if ($doc->is_obrigatorio) {
                $status = $this->arquivosEnviados[$doc->id]['status'] ?? 'pendente';
                if (in_array($status, ['pendente', 'invalido_ia', 'reprovado_manual'])) {
                    $this->dispatch('erro', msg: 'Documentos pendentes ou inválidos. Complete os uploads antes de finalizar.');
                    return;
                }
            }
        }

        if ($this->inscricaoAtual) {
            $this->inscricaoAtual->update(['etapa_atual' => 2]);
        }
        
        session()->flash('sucesso', 'Sua documentação foi enviada com sucesso para a secretaria!');
        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.document-manager');
    }
}