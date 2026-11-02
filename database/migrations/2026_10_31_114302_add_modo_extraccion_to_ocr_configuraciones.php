<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocr_configuraciones', function (Blueprint $table) {
            // codigo_cantidad  → código primero, cantidad al final (default, 90%)
            // cantidad_codigo  → cantidad + unidad primero, código después (INVERSIONES)
            // custom           → usar linea_regex de la config
            $table->string('modo_extraccion', 30)->default('codigo_cantidad')->after('formato_numerico');

            // Solo se usa si modo_extraccion = 'custom'
            // Debe tener grupos nombrados (?P<codigo>...) y (?P<cantidad>...)
            $table->string('linea_regex', 500)->nullable()->after('modo_extraccion');
        });
    }

    public function down(): void
    {
        Schema::table('ocr_configuraciones', function (Blueprint $table) {
            $table->dropColumn(['modo_extraccion', 'linea_regex']);
        });
    }
};