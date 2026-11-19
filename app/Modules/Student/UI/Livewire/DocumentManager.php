<?php

namespace App\Modules\Student\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
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
    public $laudoPcdUpload = null;

    public function mount()
    {
        $this->carregarStatusArquivos();
    }

    #[Computed]
    public function inscricaoAtual()
    {
        return Inscricao::with(['curso', 'unidade', 'ciclo', 'statusInscricao'])
            ->where('student_id', auth('student')->id())
            ->latest('id')
            ->first();
    }

    #[Computed]
    public function uploadHabilitadoPeloStatus(): bool
    {
        $insc = $this->inscricaoAtual;
        if (!$insc || !$insc->ciclo) {
            return false;
        }

        $statusAlvoId = $insc->ciclo->status_documentacao_id ?? null;
        if (!empty($statusAlvoId)) {
            return (int) $insc->status_inscricao_id === (int) $statusAlvoId;
        }

        return true;
    }

    #[Computed]
    public function nomeStatusExigido(): ?string
    {
        $id = $this->inscricaoAtual?->ciclo?->status_documentacao_id;
        return $id ? StatusInscricao::find($id)?->nome : null;
    }

    #[Computed]
    public function documentosExigidos()
    {
        if (!$this->inscricaoAtual) return collect();
        return DocumentoExigido::where('ciclo_id', $this->inscricaoAtual->ciclo_id)->get();
    }

    public function updatedLaudoPcdUpload(): void
    {
        if (!$this->uploadHabilitadoPeloStatus) {
            $this->dispatch('erro', msg: 'Sua inscrição ainda não está na etapa que permite o envio de documentos.');
            return;
        }

        $this->validate([
            'laudoPcdUpload' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ], [
            'laudoPcdUpload.mimes' => 'O laudo deve estar em formato PDF ou Imagem (JPG, PNG, WebP).',
            'laudoPcdUpload.max'   => 'O arquivo não pode ultrapassar 10MB.',
        ]);

        $insc = $this->inscricaoAtual;
        if (!$insc) return;

        $path = $this->laudoPcdUpload->store('inscricoes/laudos_pcd', 'public');
        $din  = is_array($insc->dados_dinamicos) ? $insc->dados_dinamicos : (json_decode($insc->dados_dinamicos ?? '{}', true) ?: []);
        $din['laudo_pcd'] = $path;

        $insc->update(['dados_dinamicos' => $din]);
        $this->laudoPcdUpload = null;
        unset($this->inscricaoAtual);

        $this->dispatch('sucesso', msg: 'Laudo médico PcD enviado com sucesso!');
    }

    public function carregarStatusArquivos()
    {
        $inscricao = $this->inscricaoAtual;
        if (!$inscricao) return;

        $documentosSalvos = DocumentoMatricula::where('inscricao_id', $inscricao->id)
            ->get()
            ->keyBy('documento_exigido_id');

        foreach ($this->documentosExigidos as $doc) {
            if ($documentosSalvos->has($doc->id)) {
                $salvo = $documentosSalvos->get($doc->id);
                $this->arquivosEnviados[$doc->id] = [
                    'id'                       => $salvo->id,
                    'status'                   => $salvo->status_analise,
                    'arquivo_caminho'          => $salvo->arquivo_caminho,
                    'tentativas'               => $salvo->tentativas_ia,
                    'motivo_rejeicao'          => $salvo->log_ia['motivo_rejeicao'] ?? '',
                    'motivo_rejeicao_humana'   => $salvo->log_ia['motivo_rejeicao_humana'] ?? '',
                    'enviado_por_participante' => !empty($salvo->log_ia['enviado_por_participante']),
                    'participante_nome'        => $salvo->log_ia['participante_nome'] ?? null,
                ];
            } else {
                $this->arquivosEnviados[$doc->id] = [
                    'id'                     => null,
                    'status'                 => 'pendente',
                    'arquivo_caminho'        => null,
                    'tentativas'             => 0,
                    'motivo_rejeicao'        => '',
                    'motivo_rejeicao_humana' => '',
                ];
            }
        }
    }

    public function updatedUploads($value, $documentoExigidoId)
    {
        if (!$this->uploadHabilitadoPeloStatus) {
            $this->dispatch('erro', msg: 'O envio de documentos ainda não está liberado para o status atual da sua inscrição.');
            return;
        }

        $this->validate([
            "uploads.{$documentoExigidoId}" => 'required|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
        ], [
            "uploads.{$documentoExigidoId}.mimes" => 'Formatos aceitos: PDF, JPEG, PNG e WebP.',
            "uploads.{$documentoExigidoId}.max"   => 'O tamanho máximo do documento é de 10MB.',
        ]);

        $file = $this->uploads[$documentoExigidoId];
        $documentoModel = DocumentoExigido::findOrFail($documentoExigidoId);
        $inscricao = $this->inscricaoAtual;

        $docMatricula = DocumentoMatricula::firstOrNew([
            'inscricao_id'         => $inscricao->id,
            'documento_exigido_id' => $documentoExigidoId,
        ]);

        if ($docMatricula->arquivo_caminho && Storage::disk('local')->exists($docMatricula->arquivo_caminho)) {
            Storage::disk('local')->delete($docMatricula->arquivo_caminho);
        }

        $caminho = $file->store("matriculas/{$inscricao->id}");
        try {
            Storage::disk('public')->put($caminho, file_get_contents($file->getRealPath()));
        } catch (\Throwable $e) {}

        $docMatricula->arquivo_caminho  = $caminho;
        $docMatricula->arquivo_extensao = $file->getClientOriginalExtension();
        $docMatricula->tentativas_ia    = ((int) $docMatricula->tentativas_ia) + 1;
        $docMatricula->save();

        $resultadoIa = AiValidationService::validarDocumento($inscricao, $documentoModel, $caminho);

        if ($resultadoIa['valido']) {
            $docMatricula->status_analise = 'valido_ia';
            $docMatricula->log_ia = $resultadoIa['raw'] ?? [];
        } else {
            $docMatricula->status_analise = ($docMatricula->tentativas_ia >= 3) ? 'analise_manual' : 'invalido_ia';
            $docMatricula->log_ia = [
                'motivo_rejeicao' => $resultadoIa['motivo_rejeicao'],
                'raw'             => $resultadoIa['raw'] ?? [],
            ];
        }

        $docMatricula->save();
        $this->carregarStatusArquivos();

        $this->dispatch('analise-concluida', docId: $documentoExigidoId);
    }

    public function finalizarMatricula()
    {
        foreach ($this->documentosExigidos as $doc) {
            if ($doc->is_obrigatorio) {
                $status = $this->arquivosEnviados[$doc->id]['status'] ?? 'pendente';
                if (in_array($status, ['pendente', 'invalido_ia', 'reprovado_manual'])) {
                    $this->dispatch('erro', msg: 'Existem documentos pendentes ou inválidos. Conclua o envio antes de finalizar.');
                    return;
                }
            }
        }

        $this->documentacaoConcluida = true;
        session()->flash('sucesso', 'Sua documentação foi enviada com sucesso para a secretaria!');
        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.document-manager');
    }
}