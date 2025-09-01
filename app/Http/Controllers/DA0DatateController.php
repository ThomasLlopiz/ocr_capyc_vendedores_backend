<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DA0DatateController extends Controller
{
    public function index()
    {
        try {
            // Consultar da1010 para obtener da1_codtab vigentes
            $tablas = DB::table('da1010')
                ->whereNotNull('da1_datini')                      // Vigencia inicio no nulo
                ->where('da1_datini', '<=', now()->format('Ymd')) // Vigente hoy
                ->where(function ($query) {
                    $query->whereNull('da1_datfim')                      // Sin fecha de fin
                        ->orWhere('da1_datfim', '>=', now()->format('Ymd')); // O fin posterior
                })
                ->select('da1_codtab')
                ->distinct()
                ->orderBy('da1_codtab')
                ->get()
                ->pluck('da1_codtab')
                ->toArray();

            Log::info("Tablas obtenidas de da1010", ['tablas' => $tablas]);
            return response()->json(['data' => $tablas], 200);
        } catch (\Exception $e) {
            Log::error("Error en DA0DatateController: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
