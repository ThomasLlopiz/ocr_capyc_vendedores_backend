<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = ['codigo', 'tienda', 'nombre', 'n_fantasia', 'cuit_cuil', 'vendedor'];

    protected $casts = [
        'codigo'    => 'integer',
        'tienda'    => 'integer',
        'cuit_cuil' => 'integer',
        'vendedor'  => 'integer',
    ];

    public $timestamps = true;
}
