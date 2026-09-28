<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tipo')->nullable(); // CLI/Job ou HTTP
            $table->string('http_code')->nullable(); // Ex: 500, 403
            $table->text('mensagem'); // Mensagem do erro
            $table->string('arquivo')->nullable(); // Onde quebrou
            $table->integer('linha')->nullable();
            $table->string('url')->nullable(); // A rota que o utilizador tentou aceder
            $table->string('metodo_http')->nullable(); // GET, POST, etc
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Quem sofreu o erro
            $table->longText('stack_trace')->nullable(); // Os detalhes técnicos profundos
            $table->boolean('resolvido')->default(false); // Para si (Developer) marcar como corrigido
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};