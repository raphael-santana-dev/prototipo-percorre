<?php

namespace App\Modules\GestaoEducacional\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoAvaliacao;
use App\Models\Turma;
use App\Models\Matricula;
use App\Modules\Student\Domain\Models\Student;

class AvaliacaoPdfController extends Controller
{
    public function exportar($periodo_id, $turma_id, $student_id)
    {
        // 1. Busca os Modelos Principais
        $periodo = PeriodoAvaliacao::with('fases', 'criterios')->findOrFail($periodo_id);
        $turma = Turma::with(['curso', 'professores'])->findOrFail($turma_id);
        $student = Student::findOrFail($student_id);
        
        // Busca a Matrícula vinculada a esta turma e aluno
        $matricula = Matricula::where('student_id', $student_id)
            ->whereHas('turmas', fn($q) => $q->where('turmas.id', $turma_id))
            ->first();

        // 2. Busca todas as fases da avaliação deste aluno
        $avaliacoes = AlunoAvaliacao::with('itens.criterio')
            ->where('periodo_id', $periodo_id)
            ->where('turma_id', $turma_id)
            ->where('student_id', $student_id)
            ->orderBy('fase', 'asc')
            ->get();

        if ($avaliacoes->isEmpty()) {
            abort(404, 'Nenhuma avaliação encontrada para exportação.');
        }

        // 3. Monta um array estruturado para facilitar a leitura na View do PDF
        $matrizRespostas = [];
        $mediasFinais = ['parcial' => [], 'final' => []];

        foreach ($periodo->criterios as $criterio) {
            foreach ($avaliacoes as $av) {
                $item = $av->itens->where('criterio_id', $criterio->id)->first();
                $nota = $item ? $item->nivel_nps : null;
                $meta = $item ? $item->aval_metas : null;

                $matrizRespostas[$criterio->id]['nome'] = $criterio->nome;
                $matrizRespostas[$criterio->id]['fases'][$av->fase] = [
                    'nota' => $nota,
                    'meta' => $meta
                ];

                // Preparação para calcular as médias no PDF
                if (is_numeric($nota)) {
                    if (in_array($av->fase, ['1', '2'])) $mediasFinais['parcial'][] = $nota;
                    if (in_array($av->fase, ['3'])) $mediasFinais['final'][] = $nota;
                }
            }
        }

        $mediaParcial = count($mediasFinais['parcial']) > 0 ? round(array_sum($mediasFinais['parcial']) / count($mediasFinais['parcial']), 1) : '-';
        $mediaFinal = count($mediasFinais['final']) > 0 ? round(array_sum($mediasFinais['final']) / count($mediasFinais['final']), 1) : '-';

        // 4. Injeta os dados na View e gera o PDF
        $pdf = Pdf::loadView('pdf.matriz-avaliacao', [
            'periodo' => $periodo,
            'turma' => $turma,
            'student' => $student,
            'matricula' => $matricula,
            'matrizRespostas' => $matrizRespostas,
            'mediaParcial' => $mediaParcial,
            'mediaFinal' => $mediaFinal,
        ]);

        // Formata o nome do arquivo dinamicamente
        $fileName = 'Matriz_Avaliacao_' . \Illuminate\Support\Str::slug($student->name) . '.pdf';

        return $pdf->download($fileName);
    }
}