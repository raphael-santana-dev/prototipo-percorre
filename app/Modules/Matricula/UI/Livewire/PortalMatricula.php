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
use Illuminate\Support\Facades\RateLimiter;

#[Layout('components.layouts.public')]
#[Title('Portal de Matrícula')]
class PortalMatricula extends Component
{
    use WithFileUploads;

    public $token;
    public $inscricao;
    public $documentosExigidos = [];
    public $uploads = [];
    public $arquivosEnviados = [];
    public $concluido = false;

    // Desafio de Identidade
    public $autenticado = false;
    public $cpf_acesso = '';
    public $data_nascimento_acesso = '';

    public function mount($token)
    {
        $this->inscricao = Inscricao::with(['curso', 'unidade', 'ciclo'])
            ->where('token_matricula', $token)
            ->firstOrFail();

        // Pula o desafio caso o estudante já possua sessão ativa correspondente
        if (auth('student')->check() && auth('student')->id() === $this->inscricao->student_id) {
            $this->autenticado = true;
        }

        $this->documentosExigidos = DocumentoExigido::where('ciclo_id', $this->inscricao->ciclo_id)->get();
        $this->carregarStatusArquivos();
    }

    public function verificarIdentidade()
    {
        $throttleKey = 'matricula-auth:' . $this->token . '|' . request()->ip();

        // Trava de segurança: máximo de 5 tentativas a cada 60 segundos
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $segundos = RateLimiter::availableIn($throttleKey);
            $this->addError('falha_auth', "Muitas tentativas incorretas. Acesso bloqueado por segurança. Tente novamente em {$segundos} segundos.");
            return;
        }

        $this->validate([
            'cpf_acesso' => 'required|min:11',
            'data_nascimento_acesso' => 'required|date'
        ], [
            'cpf_acesso.required' => 'O CPF é obrigatório.',
            'data_nascimento_acesso.required' => 'A Data de Nascimento é obrigatória.'
        ]);

        $cpfLimpo = preg_replace('/[^0-9]/', '', (string)$this->cpf_acesso);
        $cpfInscricaoLimpo = preg_replace('/[^0-9]/', '', (string)$this->inscricao->cpf);

        // Comparação segura contra Timing Attacks
        $cpfValido = hash_equals($cpfInscricaoLimpo, $cpfLimpo);
        $dataValida = !empty($this->inscricao->data_nascimento) 
            && hash_equals((string)$this->inscricao->data_nascimento, (string)$this->data_nascimento_acesso);

        if ($cpfValido && $dataValida) {
            RateLimiter::clear($throttleKey);
            $this->autenticado = true;
            $this->dispatch('sucesso', msg: 'Identidade confirmada com sucesso.');
        } else {
            RateLimiter::hit($throttleKey, 60);
            $restantes = RateLimiter::remaining($throttleKey, 5);
            $this->addError('falha_auth', "Dados incorretos. Você tem mais {$restantes} tentativa(s) antes do bloqueio.");
        }
    }

    public function carregarStatusArquivos()
    {
        $documentosSalvos = DocumentoMatricula::where('inscricao_id', $this->inscricao->id)
            ->get()
            ->keyBy('documento_exigido_id');

        foreach ($this->documentosExigidos as $doc) {
            if ($documentosSalvos->has($doc->id)) {
                $salvo = $documentosSalvos->get($doc->id);
                $this->arquivosEnviados[$doc->id] = [
                    'id' => $salvo->id,
                    'status' => $salvo->status_analise,
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
        // Limita extensão e tamanho máximo (10MB)
        $this->validate([
            "uploads.{$documentoExigidoId}" => 'required|file|mimes:jpeg,png,jpg,webp|max:10240'
        ], [
            "uploads.{$documentoExigidoId}.mimes" => 'Apenas arquivos JPEG, PNG e WebP são aceitos.',
            "uploads.{$documentoExigidoId}.max" => 'O tamanho máximo do documento é de 10MB.'
        ]);

        $file = $this->uploads[$documentoExigidoId];
        $documentoModel = DocumentoExigido::findOrFail($documentoExigidoId);

        $docMatricula = DocumentoMatricula::firstOrNew([
            'inscricao_id' => $this->inscricao->id,
            'documento_exigido_id' => $documentoExigidoId,
        ]);

        // Mitigação DoS: Remove arquivo órfão antigo antes de gravar o novo
        if ($docMatricula->arquivo_caminho && Storage::disk('local')->exists($docMatricula->arquivo_caminho)) {
            Storage::disk('local')->delete($docMatricula->arquivo_caminho);
        }

        $caminho = $file->store("matriculas/{$this->inscricao->id}");

        $docMatricula->arquivo_caminho = $caminho;
        $docMatricula->arquivo_extensao = $file->getClientOriginalExtension();
        $docMatricula->tentativas_ia = $docMatricula->tentativas_ia + 1;
        $docMatricula->save();

        $resultadoIa = AiValidationService::validarDocumento($this->inscricao, $documentoModel, $caminho);

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
    }

    public function finalizarMatricula()
    {
        foreach ($this->documentosExigidos as $doc) {
            if ($doc->is_obrigatorio) {
                $status = $this->arquivosEnviados[$doc->id]['status'] ?? 'pendente';
                if (in_array($status, ['pendente', 'invalido_ia'])) {
                    $this->dispatch('erro', msg: 'Documentos pendentes ou inválidos. Complete os uploads antes de finalizar.');
                    return;
                }
            }
        }

        $this->inscricao->update(['etapa_atual' => 'Análise de Matrícula']);
        $this->concluido = true;
    }

    public function render()
    {
        return view('livewire.matricula.portal-matricula');
    }
}