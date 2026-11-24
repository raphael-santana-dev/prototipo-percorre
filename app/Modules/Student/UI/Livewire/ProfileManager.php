<?php

namespace App\Modules\Student\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use App\Modules\Matricula\Services\AiValidationService;
use Illuminate\Support\Facades\Storage;

#[Layout('components.layouts.student-app')]
#[Title('Meu Perfil - Portal do Estudante')]
class ProfileManager extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public array $uploads = [];
    public array $uploadsLote = [];
    public array $arquivosEnviados = [];
    public bool $documentacaoConcluida = false;
    public $laudoPcdUpload = null;

    public function mount()
    {
        $student = auth('student')->user();
        if ($student && method_exists($student, 'sincronizarInscricoesVinculadas')) {
            $student->sincronizarInscricoesVinculadas();
        }

        $this->name  = (string) $student->name;
        $this->email = (string) $student->email;

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

        $this->dispatch('sucesso', msg: 'Laudo médico PcD atualizado com sucesso!');
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
                    'id'                     => $salvo->id,
                    'status'                 => $salvo->status_analise,
                    'tentativas'             => $salvo->tentativas_ia,
                    'motivo_rejeicao'        => $salvo->log_ia['motivo_rejeicao'] ?? '',
                    'motivo_rejeicao_humana' => $salvo->log_ia['motivo_rejeicao_humana'] ?? '',
                ];
            } else {
                $this->arquivosEnviados[$doc->id] = [
                    'status'                 => 'pendente',
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
            $docMatricula->status_analise = 'analise_manual';
            $docMatricula->log_ia = [
                'motivo_rejeicao' => $resultadoIa['motivo_rejeicao'] ?? 'Documento encaminhado para análise manual da secretaria.',
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
                    $this->dispatch('erro', msg: 'Existem documentos pendentes ou inválidos. Complete o envio antes de finalizar.');
                    return;
                }
            }
        }

        $this->documentacaoConcluida = true;
        $this->dispatch('sucesso', msg: 'Sua documentação foi enviada com sucesso para a secretaria!');
    }

    public function updateProfile()
    {
        $student = auth('student')->user();

        $this->validate([
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', Rule::unique('students', 'email')->ignore($student->id)],
        ]);

        $student->update([
            'name'  => $this->name,
            'email' => strtolower(trim($this->email)),
        ]);

        $this->dispatch('profile-updated');
        $this->dispatch('sucesso', msg: 'Seus dados cadastrais foram atualizados com sucesso!');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'new_password.confirmed' => 'A confirmação da nova senha não confere.',
        ]);

        $student = auth('student')->user();

        if (!Hash::check($this->current_password, $student->password)) {
            $this->addError('current_password', 'A senha atual informada está incorreta.');
            return;
        }

        $student->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('sucesso', msg: 'Sua senha foi alterada com segurança!');
    }

    public function render()
    {
        return view('livewire.student.profile-manager');
    }
}