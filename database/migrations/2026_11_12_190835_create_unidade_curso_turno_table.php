<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('unidade_curso_turno')) {
            Schema::create('unidade_curso_turno', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('unidade_id');
                $table->unsignedBigInteger('curso_id');
                $table->unsignedBigInteger('turno_id');
                $table->timestamps();

                $table->unique(['unidade_id', 'curso_id', 'turno_id'], 'uq_unidade_curso_turno');
                $table->index(['unidade_id', 'curso_id']);
                $table->index(['curso_id', 'unidade_id']);
            });

            // Migra automaticamente os vínculos atuais (curso_unidade x curso_turno) para a nova matriz
            if (Schema::hasTable('curso_unidade') && Schema::hasTable('curso_turno')) {
                $cursosUnidades = DB::table('curso_unidade')->get();
                $cursosTurnos   = DB::table('curso_turno')->get()->groupBy('curso_id');

                $inserts = [];
                $agora = now();

                foreach ($cursosUnidades as $cu) {
                    $turnosDoCurso = $cursosTurnos->get($cu->curso_id, collect());
                    foreach ($turnosDoCurso as $ct) {
                        $inserts[] = [
                            'unidade_id' => $cu->unidade_id,
                            'curso_id'   => $cu->curso_id,
                            'turno_id'   => $ct->turno_id,
                            'created_at' => $agora,
                            'updated_at' => $agora,
                        ];
                    }
                }

                foreach (array_chunk($inserts, 500) as $lote) {
                    DB::table('unidade_curso_turno')->insertOrIgnore($lote);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unidade_curso_turno');
    }
};