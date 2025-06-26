<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = ['codigo', 'tienda', 'nombre', 'n_fantasia', 'cuit_cuil', 'vendedor'];

    protected $casts = [
        'codigo'    => 'string',
        'tienda'    => 'integer',
        'cuit_cuil' => 'integer',
        'vendedor'  => 'string',
    ];

    public $timestamps = true;

    public function getCodigoAttribute($value)
    {
        return str_pad($value, 6, '0', STR_PAD_LEFT);
    }

    public function getVendedorAttribute($value)
    {
        return str_pad($value, 6, '0', STR_PAD_LEFT);
    }
}
