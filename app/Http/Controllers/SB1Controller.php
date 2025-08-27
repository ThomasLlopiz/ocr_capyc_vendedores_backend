<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SB1Controller extends Controller
{
    public function buscarCodigo(Request $request)
    {
        $codigo = $request->input('codigo');

        if (! $codigo) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibió ningún código.',
            ], 400);
        }

        // Buscar coincidencias en las 3 columnas usando ILIKE para PostgreSQL
        $resultados = DB::connection('ocr_capyc_vendedores')
            ->table('sb1010')
            ->whereRaw('UPPER(TRIM(b1_cod)) ILIKE ?', ['%' . strtoupper(trim($codigo)) . '%'])
            ->orWhereRaw('UPPER(TRIM(b1_xcodcli)) ILIKE ?', ['%' . strtoupper(trim($codigo)) . '%'])
            ->orderBy('b1_cod', 'ASC')
            ->get();

        if ($resultados->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron coincidencias.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $resultados,
        ], 200);
    }
}
