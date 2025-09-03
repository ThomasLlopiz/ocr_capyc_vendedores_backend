<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrecioController extends Controller
{
    public function buscarPrecio(Request $request)
    {
        \Log::info('📩 Datos recibidos en API:', [
            'codtab' => $request->codtab,
            'codpro' => $request->codpro,
        ]);

        $precio = DB::table('da1010')
            ->whereRaw("TRIM(da1_codtab) = TRIM(?)", [$request->codtab])
            ->whereRaw("TRIM(da1_codpro) LIKE TRIM(?)", [$request->codpro])
            ->value('da1_prcven');

        \Log::info('💰 Precio encontrado:', ['precio' => $precio]);

        return response()->json(['precio' => $precio], 200);
    }
}
