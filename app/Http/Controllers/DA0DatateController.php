<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DA0DatateController extends Controller
{
    public function index(Request $request)
    {
        try {
            $q     = trim((string) $request->query('q', ''));
            $limit = (int) $request->query('limit', 25);
            $limit = $limit <= 0 ? 25 : ($limit > 200 ? 200 : $limit);

            // ✅ No q => no opciones (evita "151" o listados por defecto)
            if ($q === '') {
                Log::info('DA0Datate index: q vacío -> []');
                return response()->json([], 200);
            }

            $driver = DB::connection()->getDriverName();

            $query = DB::table('da0010')->select('da0_codtab', 'da0_descri');

            if ($driver === 'pgsql') {
                // PostgreSQL => ILIKE (case-insensitive nativo)
                $query->where(function ($w) use ($q) {
                    $w->where('da0_codtab', 'ILIKE', "%{$q}%")
                        ->orWhere('da0_descri', 'ILIKE', "%{$q}%");
                });
            } else {
                // MySQL / MariaDB / SQL Server => forzamos case-insensitive por UPPER
                $Q = mb_strtoupper($q, 'UTF-8');
                $query->where(function ($w) use ($Q) {
                    $w->whereRaw('UPPER(da0_codtab) LIKE ?', ["%{$Q}%"])
                        ->orWhereRaw('UPPER(da0_descri) LIKE ?', ["%{$Q}%"]);
                });
            }

            $rows = $query
                ->orderBy('da0_codtab', 'ASC')
                ->limit($limit)
                ->get()
                ->map(function ($t) {
                    return [
                        'codtab' => trim($t->da0_codtab ?? ''),
                        'descri' => trim($t->da0_descri ?? ''),
                    ];
                })
                ->values()
                ->toArray();

            Log::info('DA0Datate index', ['q' => $q, 'driver' => $driver, 'count' => count($rows)]);
            return response()->json($rows, 200);

        } catch (\Exception $e) {
            Log::error("Error en DA0DatateController: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
