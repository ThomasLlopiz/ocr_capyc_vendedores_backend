<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $codigo = trim((string) $request->query('codigo', ''));
        $tienda = trim((string) $request->query('tienda', ''));
        $search = trim((string) $request->query('search', ''));
        $limit  = (int) $request->query('limit', 200);

        $q = DB::connection('totvs')
            ->table('sa1010')
            ->select(['a1_cod', 'a1_loja', 'a1_nome', 'a1_nreduz', 'a1_vend'])
            ->where(function ($w) {
                $w->whereNull('a1_msblql')
                    ->orWhereRaw("TRIM(a1_msblql) <> '1'");
            });

        if ($codigo !== '') {
            $q->whereRaw('UPPER(TRIM(a1_cod)) = ?', [strtoupper($codigo)]);
        }
        if ($tienda !== '') {
            $q->whereRaw('TRIM(a1_loja) = ?', [trim($tienda)]);
        }
        if ($search !== '') {
            $term = '%' . strtoupper($search) . '%';
            $q->where(function ($w) use ($term) {
                $w->whereRaw('UPPER(TRIM(a1_cod)) ILIKE ?', [$term])
                    ->orWhereRaw('UPPER(TRIM(a1_nome)) ILIKE ?', [$term])
                    ->orWhereRaw('UPPER(TRIM(a1_nreduz)) ILIKE ?', [$term]);
            });
        }

        $rows = $q->orderBy('a1_cod', 'ASC')
            ->orderBy('a1_loja', 'ASC')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron coincidencias.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $rows,
        ], 200);
    }

    public function buscarCodigo(Request $request)
    {
        $codigo = $request->input('codigo', $request->query('codigo'));

        if (! $codigo) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibió ningún código.',
            ], 400);
        }

        $term = '%' . strtoupper(trim($codigo)) . '%';

        $resultados = DB::connection('totvs')
            ->table('sa1010')
            ->select(['a1_cod', 'a1_loja', 'a1_nome', 'a1_nreduz', 'a1_vend'])
            ->where(function ($q) use ($term) {
                $q->whereRaw('UPPER(TRIM(a1_cod)) ILIKE ?', [$term])
                    ->orWhereRaw('UPPER(TRIM(a1_nome)) ILIKE ?', [$term])
                    ->orWhereRaw('UPPER(TRIM(a1_nreduz)) ILIKE ?', [$term]);
            })
            ->where(function ($w) {
                $w->whereNull('a1_msblql')
                    ->orWhereRaw("TRIM(a1_msblql) <> '1'");
            })
            ->orderBy('a1_cod', 'ASC')
            ->orderBy('a1_loja', 'ASC')
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

    public function buscarPorNombre(Request $request): JsonResponse
    {
        $nombre = trim((string) $request->input('nombre', ''));
        if ($nombre === '') {
            return response()->json([
                'success' => false,
                'message' => 'Parámetro "nombre" requerido.',
            ], 400);
        }

        $rows = DB::connection('totvs')
            ->table('sa1010')
            ->select(['a1_cod', 'a1_loja', 'a1_nome', 'a1_nreduz', 'a1_vend'])
            ->whereRaw('UPPER(TRIM(a1_nome)) ILIKE ?', ['%' . strtoupper($nombre) . '%'])
            ->where(function ($w) {
                $w->whereNull('a1_msblql')
                    ->orWhereRaw("TRIM(a1_msblql) <> '1'");
            })
            ->orderBy('a1_cod')
            ->orderBy('a1_loja')
            ->limit(50)
            ->get();

        $data = $rows->map(function ($r) {
            return [
                'codigo'   => str_pad(trim($r->a1_cod ?? ''), 6, '0', STR_PAD_LEFT),
                'tienda'   => trim($r->a1_loja ?? ''),
                'nombre'   => trim($r->a1_nome ?? ''),
                'nreduz'   => trim($r->a1_nreduz ?? ''),
                'vendedor' => str_pad(trim($r->a1_vend ?? ''), 6, '0', STR_PAD_LEFT),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
