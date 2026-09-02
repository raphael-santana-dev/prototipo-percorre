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

    // Variáveis de Segurança (Desafio de Identidade)
    public $autenticado = false;
    public $cpf_acesso = '';
    public $data_nascimento_acesso = '';

    public function mount($token)
    {
        $this->inscricao = Inscricao::with(['curso', 'unidade', 'ciclo'])
            ->where('token_matricula', $token)
            ->firstOrFail();

        // Se o usuário já estiver logado no sistema como o Aluno dono desta inscrição, pula o desafio
        if (auth('student')->check() && auth('student')->id() === $this->inscricao->student_id) {
            $this->autenticado = true;
        }

        $this->documentosExigidos = DocumentoExigido::where('ciclo_id', $this->inscricao->ciclo_id)->get();
        $this->carregarStatusArquivos();
    }

    public function verificarIdentidade()
    {
        $this->validate([
            'cpf_acesso' => 'required|min:11',
            'data_nascimento_acesso' => 'required|date'
        ], [
            'cpf_acesso.required' => 'O CPF é obrigatório.',
            'data_nascimento_acesso.required' => 'A Data de Nascimento é obrigatória.'
        ]);

        $cpfLimpo = preg_replace('/[^0-9]/', '', $this->cpf_acesso);
        $cpfInscricaoLimpo = preg_replace('/[^0-9]/', '', $this->inscricao->cpf);

        if ($cpfLimpo === $cpfInscricaoLimpo && $this->data_nascimento_acesso === $this->inscricao->data_nascimento) {
            $this->autenticado = true;
            $this->dispatch('sucesso', msg: 'Identidade confirmada. Cofre liberado!');
        } else {
            $this->addError('falha_auth', 'Os dados informados não conferem com o titular desta matrícula.');
        }
    }

    public function carregarStatusArquivos()
    {
        $documentosSalvos = DocumentoMatricula::where('inscricao_id', $this->inscricao->id)->get()->keyBy('documento_exigido_id');

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
        $this->validate(["uploads.{$documentoExigidoId}" => 'image|max:10240']);

        $file = $this->uploads[$documentoExigidoId];
        $documentoModel = DocumentoExigido::find($documentoExigidoId);
        $caminho = $file->store("matriculas/{$this->inscricao->id}");
        
        $docMatricula = DocumentoMatricula::firstOrNew([
            'inscricao_id' => $this->inscricao->id,
            'documento_exigido_id' => $documentoExigidoId,
        ]);

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
            $docMatricula->log_ia = ['motivo_rejeicao' => $resultadoIa['motivo_rejeicao'], 'raw' => $resultadoIa['raw'] ?? []];
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
                    $this->dispatch('erro', msg: 'Você possui documentos pendentes ou inválidos. Faça o upload corretamente.');
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