<?php

namespace App\Modules\Auth\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use Livewire\WithFileUploads;
use App\Models\Inscricao;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use App\Modules\Matricula\Services\AiValidationService;
use Illuminate\Support\Facades\Storage;

#[Layout('components.layouts.app')]
#[Title('Meu Perfil - Instituto Percorre')]
class ProfileManager extends Component
{
    public string $name = '';
    public string $email = '';

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    use WithFileUploads;

    public $inscricaoAtual;
    public $documentosExigidos = [];
    public $uploads = [];
    public $uploadsLote = [];
    public $arquivosEnviados = [];
    public $documentacaoConcluida = false;

    public function mount()
    {
        $student = auth('student')->user();
        
        $this->name = $student->name;
        $this->email = $student->email;

        // Busca a inscrição mais recente para carregar a documentação
        $this->inscricaoAtual = Inscricao::with(['curso', 'unidade', 'ciclo'])
            ->where('student_id', $student->id)
            ->latest()
            ->first();

        if ($this->inscricaoAtual) {
            $this->documentosExigidos = DocumentoExigido::where('ciclo_id', $this->inscricaoAtual->ciclo_id)->get();
            $this->carregarStatusArquivos();

            // Se o estudante já enviou os documentos e avançou, oculta o formulário de upload
            if ($this->inscricaoAtual->etapa_atual >= 2) {
                $this->documentacaoConcluida = true;
            }
        }
    }

    public function updateProfile()
    {
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', Rule::unique(get_class($user), 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name' => $this->name,
            'email' => strtolower($this->email),
        ]);

        $this->dispatch('profile-updated');
        $this->dispatch('sucesso', msg: 'Seus dados foram atualizados com sucesso!');

    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'new_password.confirmed' => 'A confirmação de senha não confere.',
        ]);

        $user = auth()->user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'A senha atual está incorreta.');
            return;
        }

        $user->update([
            'password' => Hash::make($this->new_password)
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('sucesso', msg: 'Senha alterada com segurança!');
    }

    public function carregarStatusArquivos()
    {
        if (!$this->inscricaoAtual) return;

        $documentosSalvos = DocumentoMatricula::where('inscricao_id', $this->inscricaoAtual->id)
            ->get()
            ->keyBy('documento_exigido_id');

        foreach ($this->documentosExigidos as $doc) {
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

        $docMatricula = DocumentoMatricula::firstOrNew([
            'inscricao_id' => $this->inscricaoAtual->id,
            'documento_exigido_id' => $documentoExigidoId,
        ]);

        if ($docMatricula->arquivo_caminho && Storage::disk('local')->exists($docMatricula->arquivo_caminho)) {
            Storage::disk('local')->delete($docMatricula->arquivo_caminho);
        }

        $caminho = $file->store("matriculas/{$this->inscricaoAtual->id}");

        $docMatricula->arquivo_caminho = $caminho;
        $docMatricula->arquivo_extensao = $file->getClientOriginalExtension();
        $docMatricula->tentativas_ia = $docMatricula->tentativas_ia + 1;
        $docMatricula->save();

        $resultadoIa = AiValidationService::validarDocumento($this->inscricaoAtual, $documentoModel, $caminho);

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

        foreach ($this->uploadsLote as $file) {
            $caminhoTemp = $file->store("matriculas/{$this->inscricaoAtual->id}/temp");
            
            $resultadoIa = AiValidationService::classificarDocumentoLote($this->inscricaoAtual, $this->documentosExigidos, $caminhoTemp);

            if ($resultadoIa['documento_id'] > 0) {
                $docId = $resultadoIa['documento_id'];
                
                $caminhoFinal = str_replace('/temp/', '/', $caminhoTemp);
                Storage::move($caminhoTemp, $caminhoFinal);

                $docMatricula = DocumentoMatricula::firstOrNew([
                    'inscricao_id' => $this->inscricaoAtual->id,
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
        
        $this->dispatch('lote-concluido', msg: "Processamento concluído: {$sucessos} documento(s) válido(s) e alocado(s). {$falhas} ignorado(s) ou inválido(s).");
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

        $this->inscricaoAtual->update(['etapa_atual' => 2]);
        $this->documentacaoConcluida = true;
        $this->dispatch('sucesso', msg: 'Sua documentação foi enviada com sucesso para a secretaria!');
    }

    public function render()
    {
        return view('livewire.auth.profile-manager');
    }
}