<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SB1Controller extends Controller
{
    public function buscarCodigo(Request $request)
    {
        $codigo = $request->input('codigo');

        \Log::info('[SB1Controller] Request recibido', [
            'codigo_raw' => $codigo,
        ]);

        if (!$codigo) {
            \Log::warning('[SB1Controller] Código vacío en request');
            return response()->json([
                'success' => false,
                'message' => 'No se recibió ningún código.',
            ], 400);
        }

        $codigo = trim($codigo);

        // Ver qué conexión y DB se están usando
        $dbInfo = DB::connection('totvs')->select('select current_database(), current_user');
        \Log::info('[SB1Controller] Info conexión totvs', [
            'dbInfo' => $dbInfo,
        ]);

        $resultados = DB::connection('totvs')
            ->table('sb1010')
            ->where(function ($query) use ($codigo) {
                $query->where('b1_cod', 'ILIKE', "%{$codigo}%")
                    ->orWhere('b1_xcodcli', 'ILIKE', "%{$codigo}%");
            })
            ->where('b1_msblql', '<>', 1)
            ->orderBy('b1_cod', 'ASC')
            ->get();

        \Log::info('[SB1Controller] Resultados búsqueda', [
            'codigo' => $codigo,
            'count' => $resultados->count(),
        ]);

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
