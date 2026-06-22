<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SB1Controller extends Controller
{
    public function buscarCodigo(Request $request)
    {
        $codigo = $request->input('codigo');
        $codigo = trim($codigo);

        Log::info("🔍 Buscando código: " . $codigo);

        $resultados = DB::connection('totvs')
            ->table('sb1010')
            ->selectRaw(
                "b1_cod, 
                b1_xcodcli, 
                b1_um,
                b1_conv,
                b1_cxxpl,
                convert_from(convert_to(b1_desc, 'UTF8'), 'UTF8') as b1_desc"
            )
            ->where(function ($query) use ($codigo) {
                $query->whereRaw("b1_cod::text ILIKE ?", ["%{$codigo}%"])
                    ->orWhereRaw("b1_xcodcli::text ILIKE ?", ["%{$codigo}%"]);
            })
            ->where('b1_msblql', '<>', 1)
            ->get();

        Log::info("📊 Resultados encontrados: " . $resultados->count());

        foreach ($resultados as $row) {
            Log::info("  📦 Código: {$row->b1_cod}");
            Log::info("     b1_conv: {$row->b1_conv}");
            Log::info("     b1_cxxpl: {$row->b1_cxxpl}");
            Log::info("     b1_xcodcli: {$row->b1_xcodcli}");
        }

        if ($resultados->isEmpty()) {
            Log::warning("❌ No se encontraron coincidencias para: " . $codigo);
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