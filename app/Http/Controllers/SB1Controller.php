<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SB1Controller extends Controller
{
    public function buscarCodigo(Request $request)
    {
        $codigo = $request->input('codigo');

        $codigo = trim($codigo);
        $dbInfo = DB::connection('totvs')->select('select current_database(), current_user');
        $resultados = DB::connection('totvs')
            ->table('sb1010')
            ->selectRaw(
                "b1_cod, 
                b1_xcodcli, 
                b1_um,
                convert_from(convert_to(b1_desc, 'UTF8'), 'UTF8') as b1_desc"
            )
            ->where(function ($query) use ($codigo) {
                // Buscamos usando un cast limpio
                $query->whereRaw("b1_cod::text ILIKE ?", ["%{$codigo}%"])
                    ->orWhereRaw("b1_xcodcli::text ILIKE ?", ["%{$codigo}%"]);
            })
            ->where('b1_msblql', '<>', 1)
            ->get();

        if ($resultados->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron coincidencias.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $resultados,
        ], 200);
    }
}
