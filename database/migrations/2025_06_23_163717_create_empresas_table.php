<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmpresasTable extends Migration
{
    public function up()
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('codigo')->unsigned()->unique()->comment('Código de 6 dígitos (relleno con ceros a la izquierda)');
            $table->unsignedSmallInteger('tienda')->comment('Número de tienda de 1 a 4 dígitos');
            $table->string('nombre', 200)->comment('Nombre de la empresa, máximo 200 caracteres');
            $table->string('n_fantasia', 200)->comment('Nombre de fantasía, máximo 200 caracteres');
            $table->bigInteger('cuit_cuil')->unsigned()->comment('CUIT/CUIL de 11 dígitos');
            $table->bigInteger('vendedor')->unsigned()->comment('Código de vendedor de 6 dígitos (relleno con ceros a la izquierda)');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('empresas');
    }
}
