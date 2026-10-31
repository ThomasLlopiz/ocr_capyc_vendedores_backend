<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionOcr extends Model
{
    protected $table = 'ocr_configuraciones';

    protected $fillable = [
        'codigo',
        'tienda',
        'filial',
        'nombre',
        'nombre_fantasia',
        'cuit_cuil',
        'vendedor',
        'cliente_codigo_confirmado',
        'formato_numerico',
        'identificadores_texto',
        'identificadores_ocr',
        'email_origen',
        'columnas_codigo',
        'columnas_cantidad',
        'name',
        'active',
        'priority',
        'version',
        'empresa_id',
    ];

    protected $casts = [
        'cliente_codigo_confirmado' => 'boolean',
        'active' => 'boolean',
        'priority' => 'integer',
        'version' => 'integer',
        'identificadores_texto' => 'array',
        'identificadores_ocr' => 'array',
        'email_origen' => 'array',
        'columnas_codigo' => 'array',
        'columnas_cantidad' => 'array',
    ];

    public const FORMATOS_NUMERICOS = [
        'int_sin' => 'El PDF muestra: 1200 (sin separadores)',
        'decimal_solo_coma' => 'El PDF muestra: 1200,50 (coma decimal)',
        'decimal_solo_punto' => 'El PDF muestra: 1200.50 (punto decimal)',
        'miles_punto' => 'El PDF muestra: 1.200,50 o 1.200 (miles con punto)',
        'miles_coma' => 'El PDF muestra: 1,200.50 o 1,200 (miles con coma)',
    ];
}