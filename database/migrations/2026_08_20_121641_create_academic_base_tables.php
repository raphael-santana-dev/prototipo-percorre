<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('ano', 4)->nullable(); 
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('professor_turma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); 
            $table->timestamps();
            
            $table->unique(['turma_id', 'user_id']); 
        });

        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_matricula')->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos');
            $table->foreignId('unidade_id')->constrained('unidades');
            $table->foreignId('turno_id')->constrained('turnos');
            $table->string('status')->default('ativa'); 
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('matricula_turma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['matricula_id', 'turma_id']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matricula_turma');
        Schema::dropIfExists('matriculas');
        Schema::dropIfExists('professor_turma');
        Schema::dropIfExists('turmas');
    }
};