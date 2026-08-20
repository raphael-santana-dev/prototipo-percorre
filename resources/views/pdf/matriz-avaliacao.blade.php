<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Matriz de Avaliação</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        
        .header { text-align: center; padding-bottom: 10px; margin-bottom: 20px; border-bottom: 2px solid #7E1FA2; }
        .header h1 { margin: 0; color: #7E1FA2; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 3px 0 0 0; color: #666; font-size: 11px; }

        .row { width: 100%; clear: both; margin-bottom: 15px; }
        .col-6 { width: 48%; float: left; }
        .col-6.right { float: right; }
        
        .box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px; background-color: #fdfdfd; min-height: 80px; }
        .box-title { font-weight: bold; color: #7E1FA2; text-transform: uppercase; font-size: 9px; margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 3px; }
        .info-line { margin-bottom: 3px; }
        .info-label { font-weight: bold; color: #555; }

        .medias-box { text-align: center; border: 1px solid #7E1FA2; background-color: #f4e8ff; padding: 10px; border-radius: 4px; margin-bottom: 20px; clear: both;}
        .medias-box h3 { margin: 0; color: #7E1FA2; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        th { background-color: #f9fafb; color: #444; font-size: 10px; text-transform: uppercase; text-align: left; }
        .text-center { text-align: center; }
        
        .nota-badge { display: inline-block; padding: 2px 6px; background: #eee; border-radius: 3px; font-weight: bold; font-size: 12px; }
        .meta-text { font-style: italic; color: #666; font-size: 10px; margin-top: 4px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Ficha de Avaliação Socioemocional</h1>
        <p>Documento oficial extraído do Portal do Aluno</p>
    </div>

    <div class="row">
        <!-- DADOS DO ESTUDANTE -->
        <div class="col-6">
            <div class="box">
                <div class="box-title">Dados do Estudante</div>
                <div class="info-line"><span class="info-label">Nome:</span> {{ $student->name }}</div>
                <div class="info-line"><span class="info-label">CPF:</span> {{ $student->cpf }}</div>
                <div class="info-line"><span class="info-label">E-mail:</span> {{ $student->email }}</div>
                <div class="info-line"><span class="info-label">Matrícula:</span> {{ $matricula->numero_matricula ?? 'Sem matrícula vinculada' }}</div>
            </div>
        </div>

        <!-- DADOS ACADÊMICOS -->
        <div class="col-6 right">
            <div class="box">
                <div class="box-title">Dados Acadêmicos</div>
                <div class="info-line"><span class="info-label">Curso:</span> {{ $turma->curso->nome ?? 'N/A' }}</div>
                <div class="info-line"><span class="info-label">Turma:</span> {{ $turma->nome }} ({{ $turma->ano }})</div>
                <div class="info-line"><span class="info-label">Ciclo Referência:</span> Ano {{ $periodo->ano }} - C{{ $periodo->ciclo }}</div>
                <div class="info-line"><span class="info-label">Professores:</span> 
                    @if($turma->professores->isNotEmpty())
                        {{ $turma->professores->pluck('name')->join(', ') }}
                    @else
                        Nenhum professor vinculado
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="medias-box">
        <h3>Média Parcial (F1 e F2): <b>{{ $mediaParcial }}</b> &nbsp;&nbsp;|&nbsp;&nbsp; Média Final (F3): <b>{{ $mediaFinal }}</b></h3>
    </div>

    <!-- MATRIZ DE RESPOSTAS -->
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Critério Avaliado</th>
                <th style="width: 25%;" class="text-center">Fase 1 (Autoavaliação)</th>
                <th style="width: 25%;" class="text-center">Fase 2 (Professor)</th>
                <th style="width: 25%;" class="text-center">Fase 3 (Consolidação)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matrizRespostas as $critId => $dados)
                <tr>
                    <td><b>{{ $dados['nome'] }}</b></td>
                    
                    @foreach(['1', '2', '3'] as $f)
                        <td class="text-center">
                            @if(isset($dados['fases'][$f]))
                                @php 
                                    $nota = $dados['fases'][$f]['nota'];
                                    $meta = $dados['fases'][$f]['meta'];
                                @endphp
                                
                                <div><span class="info-label">NPS:</span> 
                                    @if(is_numeric($nota)) 
                                        <span class="nota-badge">{{ $nota }}</span> 
                                    @else 
                                        - 
                                    @endif
                                </div>
                                
                                @if($meta)
                                    <div class="meta-text">"{{ $meta }}"</div>
                                @endif
                            @else
                                <span style="color:#aaa;">Não registrada</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 9px; text-align: center; color: #999;">
        Documento gerado eletronicamente em {{ now()->format('d/m/Y \à\s H:i:s') }}.
    </div>

</body>
</html>