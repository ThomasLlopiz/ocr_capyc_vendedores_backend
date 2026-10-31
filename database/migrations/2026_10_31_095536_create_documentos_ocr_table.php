<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documentos_ocr', function (Blueprint $table) {
            $table->id();

            // Identificación del archivo
            $table->string('original_name', 255);       // "000001.pdf" (nombre visible)
            $table->string('storage_path', 500);         // "pdfs/000001.pdf" (relativo a base_path)
            $table->string('sha256', 64)->unique();      // dedup: no procesar dos veces el mismo PDF

            // Estado del procesamiento OCR
            $table->string('status', 20)->default('pending')->index();
            // pending | queued | processing | review | accepted | failed

            // Resultado del OCR (el JSON completo que devuelve ocr_cli.py)
            $table->jsonb('extracted')->nullable();

            // Error si el OCR falla
            $table->text('error')->nullable();

            // Cartera del vendedor (se llena cuando el operador "acepta" el documento)
            $table->string('assigned_seller_code', 20)->nullable()->index();
            $table->boolean('seller_portfolio')->default(false)->index();

            // Quién subió el archivo
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Query típica del vendedor: "mis documentos aceptados"
            $table->index(
                ['status', 'seller_portfolio', 'assigned_seller_code'],
                'doc_ocr_seller_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_ocr');
    }
};