<?php

namespace App\Jobs;

use App\Models\Inscricao;
use App\Models\SuriContato;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\Teste\Suri\Services\SuriApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SincronizarContatoSuriJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public ?int $inscricaoId = null,
        public ?int $studentId = null,
        public ?string $nomeManual = null,
        public ?string $telefoneManual = null,
        public ?string $emailManual = null,
        public ?string $cpfManual = null
    ) {
    }

    public function handle(SuriApiService $suriService): void
    {
        $inscricao = $this->inscricaoId ? Inscricao::find($this->inscricaoId) : null;
        $student   = $this->studentId
            ? Student::find($this->studentId)
            : ($inscricao && $inscricao->student_id ? Student::find($inscricao->student_id) : null);

        $nome = trim((string) ($this->nomeManual ?: ($inscricao?->nome !== 'Candidato em Preenchimento' ? $inscricao?->nome : '') ?: $student?->name));
        $foneBruto = (string) ($this->telefoneManual ?: $inscricao?->celular ?: '');
        $email = trim((string) ($this->emailManual ?: $inscricao?->email ?: $student?->email ?: ''));
        $cpf   = preg_replace('/\D/', '', (string) ($this->cpfManual ?: $inscricao?->cpf ?: ''));

        // Ignora se o telefone estiver ofuscado por LGPD (com *) ou incompleto
        if ($nome === '' || strlen($nome) < 2 || str_contains($foneBruto, '*')) {
            return;
        }

        $telefoneCom55 = $suriService->normalizarTelefoneComDdi($foneBruto);
        if (!$telefoneCom55) {
            return;
        }

        // Se ainda não há student_id na inscrição mas já existe um Student com o mesmo e-mail, vincula
        if (!$student && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $student = Student::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($student && $inscricao && !$inscricao->student_id) {
                $inscricao->update(['student_id' => $student->id]);
            }
        }

        // Verifica se já existe registro em suri_contatos para evitar chamadas repetidas desnecessárias
        $contatoExistente = SuriContato::query()
            ->when($inscricao, fn($q) => $q->orWhere('inscricao_id', $inscricao->id))
            ->when($student, fn($q) => $q->orWhere('student_id', $student->id))
            ->orWhere('telefone', $telefoneCom55)
            ->first();

        if (
            $contatoExistente &&
            !empty($contatoExistente->suri_user_id) &&
            $contatoExistente->telefone === $telefoneCom55 &&
            $contatoExistente->nome === $nome
        ) {
            // Apenas garante que os vínculos inscricao_id e student_id estejam atualizados
            $contatoExistente->update([
                'inscricao_id' => $contatoExistente->inscricao_id ?: $inscricao?->id,
                'student_id'   => $contatoExistente->student_id ?: $student?->id,
                'email'        => $email ?: $contatoExistente->email,
                'documento'    => $cpf ?: $contatoExistente->documento,
            ]);
            return;
        }

        // Chama POST /api/contacts na Suri para importar/atualizar o contato
        $res = $suriService->importarContato($nome, $telefoneCom55);

        $channelId = $suriService->getChannelId();
        $suriUserId = data_get($res, 'resposta.data.id')
            ?? data_get($res, 'resposta.id')
            ?? data_get($res, 'resposta.data.userId')
            ?? ($channelId ? "{$channelId}:{$telefoneCom55}" : $telefoneCom55);

        SuriContato::updateOrCreate(
            ['suri_user_id' => (string) $suriUserId],
            [
                'inscricao_id'        => $inscricao?->id ?? $contatoExistente?->inscricao_id,
                'student_id'          => $student?->id ?? $contatoExistente?->student_id,
                'nome'                => $nome,
                'telefone'            => $telefoneCom55,
                'email'               => $email ?: null,
                'documento'           => $cpf ?: null,
                'channel_id'          => $channelId ?: null,
                'channel_type'        => 1,
                'ultima_interacao_em' => now(),
            ]
        );
    }
}