<?php

namespace App\Modules\Student\Services;

use App\Models\Inscricao;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\Comunicacao\Services\AutomacaoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentAccessService
{
    /**
     * Garante a criação/ativação do acesso do estudante para uma inscrição.
     *
     * @return array{ok: bool, criado: bool, ja_existia_ativo: bool, motivo?: string, student?: Student, senha_provisoria?: string}
     */
    public static function garantirAcesso(
        Inscricao $inscricao,
        bool $marcarMatriculado = false,
        bool $dispararNotificacao = true
    ): array {
        $emailLimpo = strtolower(trim((string) ($inscricao->email ?? '')));
        $cpfLimpo   = preg_replace('/\D/', '', (string) ($inscricao->cpf ?? ''));

        $student = $inscricao->student_id ? Student::find($inscricao->student_id) : null;

        if (!$student && ($emailLimpo !== '' || strlen($cpfLimpo) === 11)) {
            $student = Student::query()
                ->where(function ($q) use ($emailLimpo, $cpfLimpo) {
                    if ($emailLimpo !== '') {
                        $q->orWhereRaw('LOWER(email) = ?', [$emailLimpo]);
                    }
                    if (strlen($cpfLimpo) === 11) {
                        $q->orWhereRaw("REGEXP_REPLACE(cpf, '[^0-9]', '', 'g') = ?", [$cpfLimpo]);
                    }
                })
                ->first();
        }

        if (!$student && $emailLimpo === '') {
            return [
                'ok'               => false,
                'criado'           => false,
                'ja_existia_ativo' => false,
                'motivo'           => 'Inscrição sem e-mail cadastrado.',
            ];
        }

        $contaNova        = false;
        $jaExistiaAtivo   = false;
        $senhaProvisoria  = strlen($cpfLimpo) >= 6 ? substr($cpfLimpo, 0, 6) : Str::random(8);

        if (!$student) {
            $student = Student::create([
                'name'        => $inscricao->nome,
                'email'       => $emailLimpo,
                'cpf'         => $inscricao->cpf ?: null,
                'unidade_id'  => $inscricao->unidade_id,
                'password'    => $senhaProvisoria,
                'is_active'   => true,
                'matriculado' => $marcarMatriculado,
                'slug'        => Str::slug($inscricao->nome) . '-' . rand(100, 999),
            ]);
            $contaNova = true;
        } else {
            $jaExistiaAtivo = (bool) $student->is_active;

            $alterou = false;
            if (!$student->is_active) {
                $student->is_active = true;
                $alterou = true;
            }
            if ($marcarMatriculado && !$student->matriculado) {
                $student->matriculado = true;
                $alterou = true;
            }
            if (empty($student->unidade_id) && $inscricao->unidade_id) {
                $student->unidade_id = $inscricao->unidade_id;
                $alterou = true;
            }
            if (empty($student->cpf) && $inscricao->cpf) {
                $student->cpf = $inscricao->cpf;
                $alterou = true;
            }

            if ($alterou) {
                $student->save();
            }
        }

        if ((int) $inscricao->student_id !== (int) $student->id) {
            $inscricao->student_id = $student->id;
            $inscricao->saveQuietly();
        }

        $senhaTexto = $contaNova
            ? $senhaProvisoria
            : 'Utilize sua senha já cadastrada (ou os 6 primeiros dígitos do CPF)';

        if ($dispararNotificacao && ($contaNova || !$jaExistiaAtivo)) {
            try {
                AutomacaoService::disparar('estudante.acesso_liberado', $inscricao, [
                    'nome_candidato'   => $inscricao->nome,
                    'email_acesso'     => $student->email,
                    'senha_provisoria' => $senhaTexto,
                    'link_login'       => route('login'),
                    'link_painel'      => route('student.dashboard'),
                ]);
            } catch (\Throwable $e) {
                Log::warning("Falha ao disparar automação estudante.acesso_liberado (Inscrição #{$inscricao->id}): " . $e->getMessage());
            }
        }

        return [
            'ok'               => true,
            'criado'           => $contaNova,
            'ja_existia_ativo' => $jaExistiaAtivo,
            'student'          => $student,
            'senha_provisoria' => $senhaTexto,
        ];
    }
}