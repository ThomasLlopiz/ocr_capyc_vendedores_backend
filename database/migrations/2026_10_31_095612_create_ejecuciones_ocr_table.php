<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ejecuciones_ocr', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained('documentos_ocr')
                ->cascadeOnDelete();

            // Estado de esta ejecución
            $table->string('status', 20);
            $table->string('previous_status', 20)->nullable();

            // Snapshot de la extracción anterior (para "Historial")
            $table->jsonb('previous_extracted')->nullable();

            // Hash de la config usada (para detectar cambios de reglas)
            $table->string('config_hash', 64)->nullable();

            $table->text('error')->nullable();

            // Log inmutable: solo created_at
            $table->timestamp('created_at')->useCurrent();

            $table->index(['document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejecuciones_ocr');
    }
};