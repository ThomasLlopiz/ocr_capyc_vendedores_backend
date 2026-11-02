<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ocr_configuraciones', function (Blueprint $table) {
            $table->string('modo_extraccion', 30)->default('codigo_cantidad')->after('formato_numerico');
            $table->string('linea_regex', 500)->nullable()->after('modo_extraccion');
            $table->string('codigo_regex', 500)->nullable()->after('linea_regex');
        });
    }

    public function down(): void
    {
        Schema::table('ocr_configuraciones', function (Blueprint $table) {
            $table->dropColumn(['modo_extraccion', 'linea_regex', 'codigo_regex']);
        });
    }
};