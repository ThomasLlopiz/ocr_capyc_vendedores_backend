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
            $tablas = DB::table('da0010')
                ->where(function ($query) {
                    $query->whereNull('da0_datate')
                        ->orWhereRaw("TRIM(da0_datate) = ''")
                        ->orWhere('da0_datate', '>', now());
                })
                ->select(DB::raw("CONCAT(TRIM(da0_codtab), '-', TRIM(da0_descri)) AS tabla"))
                ->pluck('tabla');

            return response()->json($tablas, 200);

        } catch (\Exception $e) {
            Log::error("Error en DA0DatateController: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
