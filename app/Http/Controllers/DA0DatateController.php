<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DA0DatateController extends Controller
{
    public function index()
    {
        try {
            $tablas = DB::table('da0010')
                ->select('da0_codtab', 'da0_descri')
                ->orderBy('da0_codtab', 'ASC')
                ->get()
                ->map(function ($tabla) {
                    return [
                        'codtab' => trim($tabla->da0_codtab ?? ''),
                        'descri' => trim($tabla->da0_descri ?? ''),
                    ];
                })
                ->values()
                ->toArray();

            Log::info('Tablas enviadas a la API: ' . json_encode($tablas));
            return response()->json($tablas, 200);

        } catch (\Exception $e) {
            Log::error("Error en DA0DatateController: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
