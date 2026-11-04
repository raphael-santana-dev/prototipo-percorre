<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use App\Models\Curso;
use App\Models\Formulario;
use App\Models\CampoFormulario;
use App\Models\AuditoriaLog;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use App\Modules\Matricula\Services\AiValidationService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

#[Layout('components.layouts.app')]
#[Title('Detalhes da Inscrição')]
class RegistrationDetails extends Component
{
    use WithFileUploads;
    use \App\Traits\WithFichaPdfDownload;

    public Inscricao $inscricao;
    public $status_selecionado;
    public bool $modalAntiSpamAberto = false;

    public string $abaAtiva = 'geral'; // geral, documentacao, formularios, movimentacao, comunicacao
    public bool $modalEmailAberto = false;
    public $emailVisualizacao = null;

    // =========================================================================
    // EDIÇÃO ASSISTIDA POR PARTICIPANTE (FUNCIONÁRIO)
    // =========================================================================
    public bool $modoEdicaoAssistida = false;
    public array $formFixo = [];
    public array $formDinamico = [];

    // =========================================================================
    // ENVIO DE DOCUMENTAÇÃO PELO PARTICIPANTE (COM OU SEM IA)
    // =========================================================================
    public bool $validarComIa = true;
    public array $uploads = [];
    public array $uploadsLote = [];
    public array $arquivosEnviados = [];

    // =========================================================================
    // PREENCHIMENTO DE FORMULÁRIOS VIA EMBED PELO PARTICIPANTE
    // =========================================================================
    public string $tipoFormularioEmbed = 'inscricao'; // 'inscricao' ou 'formulario_sistema'
    public $formularioEmbedId = '';
    public ?string $urlEmbedAtiva = null;
    public string $tituloEmbedAtivo = '';

    public function mount($id)
    {
        $this->inscricao = Inscricao::with(['unidade', 'curso', 'turno', 'ciclo'])->findOrFail($id);
        $this->status_selecionado = $this->inscricao->status_inscricao_id;
        $this->carregarDadosEdicao();
        $this->carregarStatusArquivos();
    }

    public function setAba(string $aba): void
    {
        $this->abaAtiva = $aba;
        if ($aba === 'documentacao') {
            $this->carregarStatusArquivos();
        }
    }

    public function usuarioPodeEditarAssistido(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->hasAnyRole(['dev', 'admin'])
            || $user->can('inscricao.editar')
            || $user->can('inscricao.criar');
    }

    // =========================================================================
    // 1. EDIÇÃO DE DADOS DA INSCRIÇÃO PELO PARTICIPANTE
    // =========================================================================

    public function carregarDadosEdicao(): void
    {
        $this->formFixo = [
            'nome'                  => $this->inscricao->nome,
            'possui_nome_social'    => $this->inscricao->possui_nome_social ?? 'nao',
            'nome_social'           => $this->inscricao->nome_social,
            'cpf'                   => $this->inscricao->cpf,
            'data_nascimento'       => $this->inscricao->data_nascimento ? \Carbon\Carbon::parse($this->inscricao->data_nascimento)->format('Y-m-d') : '',
            'celular'               => $this->inscricao->celular,
            'email'                 => $this->inscricao->email,
            'possui_deficiencia'    => $this->inscricao->possui_deficiencia ?? 'nao',
            'natureza_deficiencia'  => $this->inscricao->natureza_deficiencia,
            'cep'                   => $this->inscricao->cep,
            'logradouro'            => $this->inscricao->logradouro,
            'numero'                => $this->inscricao->numero,
            'complemento'           => $this->inscricao->complemento,
            'bairro'                => $this->inscricao->bairro,
            'cidade'                => $this->inscricao->cidade,
            'estado'                => $this->inscricao->estado,
            'regiao'                => $this->inscricao->regiao,
            'unidade_id'            => $this->inscricao->unidade_id,
            'curso_id'              => $this->inscricao->curso_id,
            'turno_id'              => $this->inscricao->turno_id,
        ];

        $dinamicos = is_string($this->inscricao->dados_dinamicos)
            ? json_decode($this->inscricao->dados_dinamicos, true)
            : ($this->inscricao->dados_dinamicos ?? []);

        $this->formDinamico = is_array($dinamicos) ? $dinamicos : [];
    }

    public function iniciarEdicaoAssistida(): void
    {
        abort_unless($this->usuarioPodeEditarAssistido(), 403, 'Sem permissão para edição assistida.');
        $this->carregarDadosEdicao();
        $this->modoEdicaoAssistida = true;
    }

    public function cancelarEdicaoAssistida(): void
    {
        $this->modoEdicaoAssistida = false;
        $this->carregarDadosEdicao();
    }

    #[Computed]
    public function camposDinamicosCiclo()
    {
        if (!$this->inscricao->ciclo_id) return collect();

        return CampoFormulario::where('ciclo_id', $this->inscricao->ciclo_id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();
    }

    public function salvarEdicaoAssistida(): void
    {
        abort_unless($this->usuarioPodeEditarAssistido(), 403, 'Sem permissão.');

        $this->validate([
            'formFixo.nome'  => 'required|string|min:3',
            'formFixo.cpf'   => 'required|string|min:11',
            'formFixo.email' => 'required|email',
        ], [
            'formFixo.nome.required'  => 'O nome do candidato é obrigatório.',
            'formFixo.cpf.required'   => 'O CPF é obrigatório.',
            'formFixo.email.required' => 'O e-mail é obrigatório.',
        ]);

        $participante = auth()->user();
        $agoraStr = now()->format('d/m/Y H:i');

        $dinamicosAntigos = is_string($this->inscricao->dados_dinamicos)
            ? json_decode($this->inscricao->dados_dinamicos, true)
            : ($this->inscricao->dados_dinamicos ?? []);
        if (!is_array($dinamicosAntigos)) $dinamicosAntigos = [];

        $mapaParticipante = $dinamicosAntigos['_preenchido_por_participante'] ?? [];
        $camposAlteradosLog = [];

        // Verifica quais campos fixos foram preenchidos/alterados pelo participante
        foreach ($this->formFixo as $campoKey => $novoVal) {
            $valAntigo = $campoKey === 'data_nascimento'
                ? ($this->inscricao->data_nascimento ? \Carbon\Carbon::parse($this->inscricao->data_nascimento)->format('Y-m-d') : '')
                : $this->inscricao->{$campoKey};

            if ((string) $novoVal !== (string) $valAntigo) {
                $mapaParticipante['fixo_' . $campoKey] = [
                    'user_id'   => $participante->id,
                    'user_nome' => $participante->name,
                    'data'      => $agoraStr,
                ];
                $camposAlteradosLog[] = $campoKey;
            }
        }

        // Verifica quais campos dinâmicos foram preenchidos/alterados pelo participante
        foreach ($this->formDinamico as $chaveDin => $novoValorDin) {
            if (str_starts_with($chaveDin, '_')) continue;

            $antigoValorDin = $dinamicosAntigos[$chaveDin] ?? null;
            if (json_encode($novoValorDin) !== json_encode($antigoValorDin)) {
                $mapaParticipante['din_' . $chaveDin] = [
                    'user_id'   => $participante->id,
                    'user_nome' => $participante->name,
                    'data'      => $agoraStr,
                ];
                $camposAlteradosLog[] = $chaveDin;
            }
        }

        // Sempre marca o selo geral de atendimento assistido se houve edição
        if (!empty($camposAlteradosLog)) {
            $mapaParticipante['_ultimo_atendimento'] = [
                'user_id'   => $participante->id,
                'user_nome' => $participante->name,
                'data'      => $agoraStr,
                'campos'    => $camposAlteradosLog,
            ];
        }

        $novosDinamicos = $this->formDinamico;
        $novosDinamicos['_preenchido_por_participante'] = $mapaParticipante;

        $this->inscricao->update([
            'nome'                 => $this->formFixo['nome'],
            'possui_nome_social'   => $this->formFixo['possui_nome_social'] ?: 'nao',
            'nome_social'          => $this->formFixo['nome_social'] ?: null,
            'cpf'                  => $this->formFixo['cpf'],
            'data_nascimento'      => !empty($this->formFixo['data_nascimento']) ? $this->formFixo['data_nascimento'] : null,
            'celular'              => $this->formFixo['celular'] ?: null,
            'email'                => $this->formFixo['email'],
            'possui_deficiencia'   => $this->formFixo['possui_deficiencia'] ?: 'nao',
            'natureza_deficiencia' => $this->formFixo['natureza_deficiencia'] ?: null,
            'cep'                  => $this->formFixo['cep'] ?: null,
            'logradouro'           => $this->formFixo['logradouro'] ?: null,
            'numero'               => $this->formFixo['numero'] ?: null,
            'complemento'          => $this->formFixo['complemento'] ?: null,
            'bairro'               => $this->formFixo['bairro'] ?: null,
            'cidade'               => $this->formFixo['cidade'] ?: null,
            'estado'               => $this->formFixo['estado'] ?: null,
            'regiao'               => $this->formFixo['regiao'] ?: null,
            'unidade_id'           => !empty($this->formFixo['unidade_id']) ? (int) $this->formFixo['unidade_id'] : null,
            'curso_id'             => !empty($this->formFixo['curso_id']) ? (int) $this->formFixo['curso_id'] : null,
            'turno_id'             => !empty($this->formFixo['turno_id']) ? (int) $this->formFixo['turno_id'] : null,
            'dados_dinamicos'      => $novosDinamicos,
        ]);

        if (!empty($camposAlteradosLog)) {
            AuditoriaLog::create([
                'tabela_alterada'     => 'inscricoes',
                'registro_id'         => $this->inscricao->id,
                'acao'                => 'edicao_assistida_participante',
                'informacao_anterior' => ['campos_alterados' => $camposAlteradosLog],
                'nova_informacao'     => [
                    'preenchido_por_participante' => true,
                    'participante_id'             => $participante->id,
                    'participante_nome'           => $participante->name,
                    'campos'                      => $camposAlteradosLog,
                ],
                'usuario_id'          => $participante->id,
                'usuario_nome'        => $participante->name,
                'usuario_role'        => method_exists($participante, 'getRoleNames') ? $participante->getRoleNames()->first() : 'participante',
                'usuario_login'       => $participante->email,
                'ip'                  => request()->ip(),
                'navegador'           => request()->userAgent(),
            ]);
        }

        $this->inscricao->refresh();
        $this->modoEdicaoAssistida = false;
        unset($this->logsMovimentacao);

        $this->dispatch('sucesso', msg: 'Dados da inscrição atualizados com sucesso pelo Participante!');
    }

    // =========================================================================
    // 2. ENVIO DE DOCUMENTOS PELO PARTICIPANTE (COM OU SEM VALIDAÇÃO POR IA)
    // =========================================================================

    #[Computed]
    public function documentosExigidos()
    {
        if (!$this->inscricao->ciclo_id) return collect();
        return DocumentoExigido::where('ciclo_id', $this->inscricao->ciclo_id)->get();
    }

    public function carregarStatusArquivos(): void
    {
        $documentosSalvos = DocumentoMatricula::where('inscricao_id', $this->inscricao->id)
            ->get()
            ->keyBy('documento_exigido_id');

        $this->arquivosEnviados = [];

        foreach ($this->documentosExigidos as $doc) {
            if ($documentosSalvos->has($doc->id)) {
                $salvo = $documentosSalvos->get($doc->id);
                $logIa = is_array($salvo->log_ia) ? $salvo->log_ia : (json_decode($salvo->log_ia ?? '{}', true) ?: []);

                $this->arquivosEnviados[$doc->id] = [
                    'id'                       => $salvo->id,
                    'status'                   => $salvo->status_analise,
                    'arquivo_caminho'          => $salvo->arquivo_caminho,
                    'tentativas'               => $salvo->tentativas_ia,
                    'motivo_rejeicao'          => $logIa['motivo_rejeicao'] ?? '',
                    'motivo_rejeicao_humana'   => $logIa['motivo_rejeicao_humana'] ?? '',
                    'enviado_por_participante' => !empty($logIa['enviado_por_participante']),
                    'participante_nome'        => $logIa['participante_nome'] ?? null,
                    'data_envio_participante'  => $logIa['data_envio_participante'] ?? null,
                    'validacao_ia_ativa'       => $logIa['validacao_ia_ativa'] ?? true,
                ];
            } else {
                $this->arquivosEnviados[$doc->id] = [
                    'id'                       => null,
                    'status'                   => 'pendente',
                    'arquivo_caminho'          => null,
                    'tentativas'               => 0,
                    'motivo_rejeicao'          => '',
                    'motivo_rejeicao_humana'   => '',
                    'enviado_por_participante' => false,
                    'participante_nome'        => null,
                    'data_envio_participante'  => null,
                    'validacao_ia_ativa'       => true,
                ];
            }
        }
    }

    public function updatedUploads($value, $documentoExigidoId): void
    {
        abort_unless($this->usuarioPodeEditarAssistido(), 403, 'Sem permissão.');

        $this->validate([
            "uploads.{$documentoExigidoId}" => 'required|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
        ], [
            "uploads.{$documentoExigidoId}.mimes" => 'Formatos aceitos: JPEG, PNG, WebP ou PDF.',
            "uploads.{$documentoExigidoId}.max"   => 'O tamanho máximo do documento é de 10MB.',
        ]);

        $file = $this->uploads[$documentoExigidoId];
        $documentoModel = DocumentoExigido::findOrFail($documentoExigidoId);
        $participante = auth()->user();

        $docMatricula = DocumentoMatricula::firstOrNew([
            'inscricao_id'         => $this->inscricao->id,
            'documento_exigido_id' => $documentoExigidoId,
        ]);

        if ($docMatricula->arquivo_caminho && Storage::disk('local')->exists($docMatricula->arquivo_caminho)) {
            Storage::disk('local')->delete($docMatricula->arquivo_caminho);
        }

        // Salva também no disco public para permitir visualização rápida pela equipe, além do local
        $caminho = $file->store("matriculas/{$this->inscricao->id}");
        try {
            Storage::disk('public')->put($caminho, file_get_contents($file->getRealPath()));
        } catch (\Throwable $e) {}

        $docMatricula->arquivo_caminho = $caminho;
        $docMatricula->arquivo_extensao = $file->getClientOriginalExtension();

        $metaParticipante = [
            'enviado_por_participante' => true,
            'participante_id'          => $participante->id,
            'participante_nome'        => $participante->name,
            'data_envio_participante'  => now()->format('d/m/Y H:i'),
            'validacao_ia_ativa'       => $this->validarComIa,
        ];

        if ($this->validarComIa) {
            $docMatricula->tentativas_ia = ((int) $docMatricula->tentativas_ia) + 1;
            $docMatricula->save();

            $resultadoIa = AiValidationService::validarDocumento($this->inscricao, $documentoModel, $caminho);

            if ($resultadoIa['valido']) {
                $docMatricula->status_analise = 'valido_ia';
                $docMatricula->log_ia = array_merge($resultadoIa['raw'] ?? [], $metaParticipante);
            } else {
                $docMatricula->status_analise = ($docMatricula->tentativas_ia >= 3) ? 'analise_manual' : 'invalido_ia';
                $docMatricula->log_ia = array_merge([
                    'motivo_rejeicao' => $resultadoIa['motivo_rejeicao'] ?? 'Rejeitado na validação por IA.',
                    'raw'             => $resultadoIa['raw'] ?? [],
                ], $metaParticipante);
            }
        } else {
            // Validação por IA Desativada pelo Participante: aprova diretamente
            $docMatricula->status_analise = 'aprovado_manual';
            $docMatricula->log_ia = array_merge([
                'observacao' => 'Documento enviado por Participante com validação por IA desativada.',
            ], $metaParticipante);
        }

        $docMatricula->save();

        // Registra na Auditoria (Timeline de Movimentação)
        AuditoriaLog::create([
            'tabela_alterada' => 'inscricoes',
            'registro_id'     => $this->inscricao->id,
            'acao'            => 'documento_enviado_participante',
            'nova_informacao' => [
                'documento'          => $documentoModel->nome,
                'status_analise'     => $docMatricula->status_analise,
                'validacao_ia_ativa' => $this->validarComIa,
                'participante_nome'  => $participante->name,
            ],
            'usuario_id'      => $participante->id,
            'usuario_nome'    => $participante->name,
            'usuario_role'    => method_exists($participante, 'getRoleNames') ? $participante->getRoleNames()->first() : 'participante',
            'usuario_login'   => $participante->email,
            'ip'              => request()->ip(),
            'navegador'       => request()->userAgent(),
        ]);

        unset($this->uploads[$documentoExigidoId]);
        unset($this->logsMovimentacao);
        $this->carregarStatusArquivos();

        $this->dispatch('sucesso', msg: $this->validarComIa
            ? "Documento '{$documentoModel->nome}' enviado pelo Participante e analisado pela IA!"
            : "Documento '{$documentoModel->nome}' enviado e validado diretamente pelo Participante (IA desativada)!");
    }

    public function aprovarDocumentoManualmente(int $documentoExigidoId): void
    {
        abort_unless($this->usuarioPodeEditarAssistido(), 403);

        $docMatricula = DocumentoMatricula::where('inscricao_id', $this->inscricao->id)
            ->where('documento_exigido_id', $documentoExigidoId)
            ->firstOrFail();

        $logAtual = is_array($docMatricula->log_ia) ? $docMatricula->log_ia : [];
        $logAtual['aprovado_por_participante'] = auth()->user()->name;
        $logAtual['data_aprovacao_manual'] = now()->format('d/m/Y H:i');

        $docMatricula->update([
            'status_analise' => 'aprovado_manual',
            'log_ia'         => $logAtual,
        ]);

        $this->carregarStatusArquivos();
        $this->dispatch('sucesso', msg: 'Documento aprovado manualmente pela equipe!');
    }

    // =========================================================================
    // 3. PREENCHIMENTO DE FORMULÁRIOS VIA EMBED PELO PARTICIPANTE
    // =========================================================================

    #[Computed]
    public function formulariosDisponiveisEmbed()
    {
        return Formulario::where('status', true)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function abrirFormularioEmbed(): void
    {
        abort_unless($this->usuarioPodeEditarAssistido(), 403);

        $participante = auth()->user();

        if ($this->tipoFormularioEmbed === 'inscricao') {
            $token = Crypt::encrypt($this->inscricao->id);
            $this->urlEmbedAtiva = route('inscricao.retomar', [
                'token'             => $token,
                'embed'             => 'true',
                'assistido'         => '1',
                'participante_id'   => $participante->id,
                'participante_nome' => $participante->name,
            ]);
            $this->tituloEmbedAtivo = 'Formulário de Inscrição do Candidato (' . ($this->inscricao->ciclo->nome ?? 'Ciclo Atual') . ')';
        } else {
            if (empty($this->formularioEmbedId)) {
                $this->dispatch('erro', msg: 'Selecione qual formulário deseja abrir para preenchimento.');
                return;
            }
            $form = Formulario::findOrFail($this->formularioEmbedId);
            $this->urlEmbedAtiva = url('/f/' . $form->slug) . '?' . http_build_query([
                'embed'             => 'true',
                'assistido'         => '1',
                'inscricao_id'      => $this->inscricao->id,
                'participante_id'   => $participante->id,
                'participante_nome' => $participante->name,
            ]);
            $this->tituloEmbedAtivo = $form->titulo . ' (' . strtoupper($form->tipo) . ')';
        }

        // Registra no JSON da inscrição e na Auditoria que o formulário foi aberto/preenchido via Atendimento Assistido
        $dinamicos = is_string($this->inscricao->dados_dinamicos)
            ? json_decode($this->inscricao->dados_dinamicos, true)
            : ($this->inscricao->dados_dinamicos ?? []);
        if (!is_array($dinamicos)) $dinamicos = [];

        $formulariosAssistidos = $dinamicos['_formularios_assistidos'] ?? [];
        $formulariosAssistidos[] = [
            'titulo'            => $this->tituloEmbedAtivo,
            'tipo'              => $this->tipoFormularioEmbed,
            'formulario_id'     => $this->tipoFormularioEmbed === 'formulario_sistema' ? (int) $this->formularioEmbedId : null,
            'participante_id'   => $participante->id,
            'participante_nome' => $participante->name,
            'data'              => now()->format('d/m/Y H:i'),
        ];
        $dinamicos['_formularios_assistidos'] = $formulariosAssistidos;

        $mapaParticipante = $dinamicos['_preenchido_por_participante'] ?? [];
        $mapaParticipante['_ultimo_atendimento'] = [
            'user_id'   => $participante->id,
            'user_nome' => $participante->name,
            'data'      => now()->format('d/m/Y H:i'),
        ];
        $dinamicos['_preenchido_por_participante'] = $mapaParticipante;

        $this->inscricao->update(['dados_dinamicos' => $dinamicos]);

        AuditoriaLog::create([
            'tabela_alterada' => 'inscricoes',
            'registro_id'     => $this->inscricao->id,
            'acao'            => 'formulario_assistido_participante',
            'nova_informacao' => [
                'formulario'        => $this->tituloEmbedAtivo,
                'participante_id'   => $participante->id,
                'participante_nome' => $participante->name,
            ],
            'usuario_id'      => $participante->id,
            'usuario_nome'    => $participante->name,
            'usuario_role'    => method_exists($participante, 'getRoleNames') ? $participante->getRoleNames()->first() : 'participante',
            'usuario_login'   => $participante->email,
            'ip'              => request()->ip(),
            'navegador'       => request()->userAgent(),
        ]);

        unset($this->logsMovimentacao);
    }

    public function fecharFormularioEmbed(): void
    {
        $this->urlEmbedAtiva = null;
        $this->tituloEmbedAtivo = '';
        $this->inscricao->refresh();
        $this->carregarDadosEdicao();
    }

    // =========================================================================
    // MÉTODOS ORIGINAIS DE CONTATOS, LOGS E STATUS
    // =========================================================================

    #[Computed]
    public function contatosRegistrados()
    {
        return \App\Models\InscricaoContato::where('inscricao_id', $this->inscricao->id)
            ->orderBy('data_contato', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function verConteudoEmail($logId)
    {
        $this->emailVisualizacao = \App\Modules\Comunicacao\Domain\Models\ComunicacaoLog::find($logId);
        $this->modalEmailAberto = true;
    }

    public function fecharModalEmail()
    {
        $this->modalEmailAberto = false;
        $this->emailVisualizacao = null;
    }

    #[Computed]
    public function logsMovimentacao()
    {
        $logsRaw = AuditoriaLog::where('tabela_alterada', 'inscricoes')
            ->where('registro_id', $this->inscricao->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $movimentacoes = [];

        foreach ($logsRaw as $log) {
            $antigo = is_string($log->informacao_anterior) ? json_decode($log->informacao_anterior, true) : $log->informacao_anterior;
            $novo = is_string($log->nova_informacao) ? json_decode($log->nova_informacao, true) : $log->nova_informacao;

            if ($log->acao === 'criacao') {
                $movimentacoes[] = [
                    'tipo'            => 'criacao',
                    'is_participante' => ($this->inscricao->origem === 'manual'),
                    'data'            => clone $log->created_at,
                    'usuario'         => $log->usuario_nome ?? 'Candidato / Sistema',
                    'mensagem'        => 'Inscrição iniciada/registrada no sistema.',
                ];
            } elseif ($log->acao === 'edicao_assistida_participante') {
                $camposList = implode(', ', $novo['campos'] ?? []);
                $movimentacoes[] = [
                    'tipo'            => 'participante',
                    'is_participante' => true,
                    'data'            => clone $log->created_at,
                    'usuario'         => $log->usuario_nome ?? 'Participante',
                    'mensagem'        => "preencheu/atualizou dados em nome do candidato (Campos: <b>{$camposList}</b>).",
                ];
            } elseif ($log->acao === 'documento_enviado_participante') {
                $docNome = $novo['documento'] ?? 'Documento';
                $modoIa = !empty($novo['validacao_ia_ativa']) ? 'Com Validação por IA' : 'Validação IA Desativada (Aprovação Direta)';
                $movimentacoes[] = [
                    'tipo'            => 'participante',
                    'is_participante' => true,
                    'data'            => clone $log->created_at,
                    'usuario'         => $log->usuario_nome ?? 'Participante',
                    'mensagem'        => "enviou o documento <b>{$docNome}</b> em nome do candidato (<i>{$modoIa}</i>).",
                ];
            } elseif ($log->acao === 'formulario_assistido_participante') {
                $formTitulo = $novo['formulario'] ?? 'Formulário';
                $movimentacoes[] = [
                    'tipo'            => 'participante',
                    'is_participante' => true,
                    'data'            => clone $log->created_at,
                    'usuario'         => $log->usuario_nome ?? 'Participante',
                    'mensagem'        => "iniciou/preencheu o formulário incorporado <b>{$formTitulo}</b> em atendimento assistido ao candidato.",
                ];
            } elseif ($log->acao === 'atualizacao') {
                if (isset($novo['status_inscricao_id'])) {
                    $statusAntigoId = $antigo['status_inscricao_id'] ?? null;
                    $statusNovoId = $novo['status_inscricao_id'];

                    $statusAntigoNome = $statusAntigoId ? (StatusInscricao::find($statusAntigoId)?->nome ?? 'Pendente') : 'Pendente';
                    $statusNovoNome = StatusInscricao::find($statusNovoId)?->nome ?? 'Desconhecido';

                    $movimentacoes[] = [
                        'tipo'            => 'status',
                        'is_participante' => !empty($log->usuario_id),
                        'data'            => clone $log->created_at,
                        'usuario'         => $log->usuario_nome ?? 'Sistema',
                        'mensagem'        => "alterou o status de <b>{$statusAntigoNome}</b> para <b>{$statusNovoNome}</b>",
                    ];
                }
            }
        }

        return $movimentacoes;
    }

    #[Computed]
    public function logsComunicacao()
    {
        $temColunaInscricao = Schema::hasColumn('comunicacao_logs', 'inscricao_id');
        $emailCandidato = trim((string) $this->inscricao->email);

        return \App\Modules\Comunicacao\Domain\Models\ComunicacaoLog::query()
            ->where(function ($q) use ($temColunaInscricao, $emailCandidato) {
                if ($temColunaInscricao) {
                    $q->where('inscricao_id', $this->inscricao->id);
                    if ($emailCandidato !== '') {
                        $q->orWhereRaw('LOWER(destinatario) = ?', [strtolower($emailCandidato)]);
                    }
                } else {
                    $q->whereRaw('LOWER(destinatario) = ?', [strtolower($emailCandidato)]);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getDataInscricao()
    {
        $dinamicos = is_string($this->inscricao->dados_dinamicos) ? json_decode($this->inscricao->dados_dinamicos, true) : ($this->inscricao->dados_dinamicos ?? []);
        $dataRaw = $dinamicos['Submission started'] ?? $dinamicos['submission started'] ?? $dinamicos['Submission Started'] ?? $this->inscricao->data_inscricao ?? $this->inscricao->created_at;

        try {
            return \Carbon\Carbon::parse($dataRaw);
        } catch (\Exception $e) {
            return clone $this->inscricao->created_at;
        }
    }

    public function getDataAtualizacao()
    {
        $dinamicos = is_string($this->inscricao->dados_dinamicos) ? json_decode($this->inscricao->dados_dinamicos, true) : ($this->inscricao->dados_dinamicos ?? []);
        $dataRaw = $dinamicos['Last updated'] ?? $dinamicos['last updated'] ?? $dinamicos['Last Updated'] ?? $this->inscricao->updated_at;

        try {
            return \Carbon\Carbon::parse($dataRaw);
        } catch (\Exception $e) {
            return clone $this->inscricao->updated_at;
        }
    }

    public function atualizarStatus()
    {
        if ($this->inscricao->status_inscricao_id == $this->status_selecionado) {
            $this->dispatch('erro', msg: 'O candidato já se encontra neste status!');
            return;
        }

        $statusPermitidos = $this->inscricao->ciclo->statusPipeline->pluck('id')->toArray();
        if (!empty($statusPermitidos) && !in_array($this->status_selecionado, $statusPermitidos)) {
            $this->dispatch('erro', msg: 'Este status não pertence ao funil deste ciclo!');
            return;
        }

        $statusNovo = StatusInscricao::find($this->status_selecionado);
        if (!$statusNovo) return;

        $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');
        $automacao = \App\Modules\Comunicacao\Domain\Models\Automacao::where('evento_gatilho', $eventoGatilho)->where('status', true)->first();

        if ($automacao) {
            $jaRecebeu = \App\Modules\Comunicacao\Domain\Models\Comunicado::where('template_id', $automacao->template_id)
                ->where('inscricao_id', $this->inscricao->id)
                ->exists();

            if ($jaRecebeu) {
                $this->modalAntiSpamAberto = true;
                return;
            }
        }

        $this->executarMudancaStatusFinal();
    }

    public function cancelarAntiSpam()
    {
        $this->modalAntiSpamAberto = false;
        $this->status_selecionado = $this->inscricao->status_inscricao_id;
    }

    public function executarMudancaStatusFinal()
    {
        $statusNovo = StatusInscricao::find($this->status_selecionado);

        $this->inscricao->status_inscricao_id = $statusNovo->id;
        $this->inscricao->save();

        $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');
        \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $this->inscricao);

        $this->inscricao->refresh();
        unset($this->logsComunicacao);
        unset($this->logsMovimentacao);
        $this->modalAntiSpamAberto = false;
        $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
    }

    public function abrirRegras()
    {
        $regrasRaw = is_string($this->inscricao->ciclo->regras_pontuacao)
            ? json_decode($this->inscricao->ciclo->regras_pontuacao, true)
            : ($this->inscricao->ciclo->regras_pontuacao ?? []);

        $detalhes = is_string($this->inscricao->pontuacao_detalhes)
            ? json_decode($this->inscricao->pontuacao_detalhes, true)
            : ($this->inscricao->pontuacao_detalhes ?? []);

        $auditoria = $detalhes['auditoria_detalhada'] ?? [];

        $agrupadas = collect($regrasRaw)->groupBy(function ($r) {
            return $r['campo'] ?? 'Regra Global';
        });

        $html = '<div class="space-y-6">';
        foreach ($agrupadas as $campo => $regras) {
            $nomeCampoFormatado = str_replace('_', ' ', strtoupper($campo));
            $html .= "<div class='bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm'>";
            $html .= "<h4 class='text-xs font-bold text-gray-500 mb-3 tracking-wider flex items-center gap-2'><i class='ph-fill ph-check-square-offset text-purpura-500'></i> {$nomeCampoFormatado}</h4>";
            $html .= "<div class='space-y-2'>";

            foreach ($regras as $regra) {
                $tipo = $regra['tipo_regra'] ?? 'padrao';
                $pontos = $regra['pontos'] ?? 0;
                $operador = $regra['operador'] ?? '=';
                $valor = $regra['valor'] ?? '';

                $valores = array_map('trim', explode(',', (string) $valor));
                $condicaoStr = match ($operador) {
                    '='       => "Igual a '{$valor}'",
                    '!='      => "Diferente de '{$valor}'",
                    '>='      => "Maior ou igual a {$valor}",
                    '<='      => "Menor ou igual a {$valor}",
                    '>'       => "Maior que {$valor}",
                    '<'       => "Menor que {$valor}",
                    'between' => "Entre " . ($valores[0] ?? '') . " e " . ($valores[1] ?? ''),
                    'in'      => "Dentre: " . implode(' ou ', $valores),
                    default   => "{$operador} {$valor}",
                };

                $pontosStr = $tipo === 'multiplicador_percentual' ? "+{$pontos}%" : "+{$pontos} pts";

                $atendida = false;
                foreach ($auditoria as $aud) {
                    $campoAuditoria = strtolower(trim($aud['campo_avaliado'] ?? ''));
                    $campoAtual = strtolower(trim($campo));

                    if ($campoAuditoria === $campoAtual || str_contains($campoAuditoria, $campoAtual) || str_contains($campoAtual, $campoAuditoria)) {
                        if ($aud['pontos_ganhos'] == $pontos) {
                            $condAudLimpa = str_replace('Exigência: ', '', $aud['condicao'] ?? '');
                            if ($condAudLimpa === $condicaoStr || str_contains($condAudLimpa, $condicaoStr) || str_contains($condicaoStr, $condAudLimpa)) {
                                $atendida = true;
                                break;
                            }
                            if (!empty($valor) && str_contains($condAudLimpa, (string) $valor)) {
                                $atendida = true;
                                break;
                            }
                        }
                    }
                }

                $bgClass = $atendida ? 'bg-green-50 border-green-200 dark:bg-green-900/30 dark:border-green-800' : 'bg-white border-gray-100 dark:bg-gray-700 dark:border-gray-600';
                $icon = $atendida ? '<i class="ph-fill ph-check-circle text-green-500 text-lg"></i>' : '<i class="ph ph-circle text-gray-300 text-lg"></i>';
                $textColor = $atendida ? 'text-green-800 dark:text-green-400' : 'text-gray-700 dark:text-gray-300';
                $ptsColor = $atendida ? 'text-green-700 bg-green-100 dark:bg-green-900/50 px-2 py-0.5 rounded' : 'text-gray-400';

                $html .= "<div class='flex items-center justify-between p-3 rounded-lg border {$bgClass}'>";
                $html .= "<div class='flex items-center gap-3'>{$icon}<span class='block text-sm font-bold {$textColor}'>{$condicaoStr}</span></div>";
                $html .= "<span class='font-black text-[10px] {$ptsColor}'>{$pontosStr}</span>";
                $html .= "</div>";
            }
            $html .= "</div></div>";
        }
        $html .= '</div>';

        $this->dispatch('load-quick-view', [
            'title'           => 'Mapa de Regras do Ciclo',
            'subtitle'        => 'Critérios avaliados para a pontuação',
            'icon'            => 'ph-list-numbers',
            'maxWidth'        => 'xl',
            'allowFullscreen' => true,
            'data'            => [
                'Critérios e Acertos do Candidato' => $html,
            ],
        ]);
    }

    public function render()
    {
        $todosStatus = $this->inscricao->ciclo ? $this->inscricao->ciclo->statusPipeline : collect();
        if ($todosStatus->isEmpty()) {
            $todosStatus = StatusInscricao::orderBy('nome')->get();
        }

        return view('livewire.registration.registration-details', [
            'todosStatus' => $todosStatus,
            'unidadesDb'  => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursosDb'    => Curso::whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
            'turnosDb'    => Turno::orderBy('nome')->get(),
        ]);
    }
}