<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrecioController extends Controller
{
    public function buscarPrecio(Request $request)
    {
        $codtab = trim($request->codtab ?? '');
        $codpro = trim($request->codpro ?? '');

        \Log::info('📩 Datos recibidos en API:', [
            'codtab' => $codtab,
            'codpro' => $codpro,
        ]);

        $precio = DB::connection('totvs')
            ->table('da1010')
            ->whereRaw('TRIM(da1_codtab) = ?', [$codtab])
            ->whereRaw('TRIM(da1_codpro) = ?', [$codpro])
            ->value('da1_prcven');

        \Log::info('💰 Precio encontrado:', ['precio' => $precio]);

        return response()->json(['precio' => $precio], 200);
    }
}
