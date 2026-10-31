<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ocr_configuraciones', function (Blueprint $table) {
            $table->id();

            // Datos de empresa (todos desde TOTVS SA1010)
            $table->string('codigo', 20)->nullable();
            $table->string('tienda', 10)->nullable();
            $table->string('filial', 10)->nullable();
            $table->string('nombre', 255)->nullable();
            $table->string('nombre_fantasia', 255)->nullable();
            $table->string('cuit_cuil', 30)->nullable();
            $table->string('vendedor', 10)->nullable();

            // Confirmación (siempre true, viene de TOTVS)
            $table->boolean('cliente_codigo_confirmado')->default(true);

            // Reglas que ingresa el usuario
            $table->string('formato_numerico', 30)->nullable();

            $table->jsonb('identificadores_texto')->nullable();
            $table->jsonb('identificadores_ocr')->nullable();
            $table->jsonb('email_origen')->nullable();
            $table->jsonb('columnas_codigo')->nullable();
            $table->jsonb('columnas_cantidad')->nullable();

            // Metadatos
            $table->string('name', 255);
            $table->boolean('active')->default(true);
            $table->integer('priority')->default(0);
            $table->integer('version')->default(1);
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();

            $table->timestamps();

            $table->index(['codigo', 'tienda', 'filial']);
            $table->index(['active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_configuraciones');
    }
};