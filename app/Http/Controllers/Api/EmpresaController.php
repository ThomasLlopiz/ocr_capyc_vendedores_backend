<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpresaController extends Controller
{
    /**
     * GET /api/empresas
     * Filtros opcionales:
     *   - ?codigo= (match exacto por a1_cod, con trim/upper)
     *   - ?tienda= (match exacto por a1_loja)
     *   - ?search= (ILIKE en a1_cod, a1_nome, a1_nreduz)
     *   - ?limit= (por defecto 200)
     */
    public function index(Request $request)
    {
        $codigo = trim((string) $request->query('codigo', ''));
        $tienda = trim((string) $request->query('tienda', ''));
        $search = trim((string) $request->query('search', ''));
        $limit  = (int) $request->query('limit', 200);

        $q = DB::connection('ocr_capyc_vendedores')
            ->table('sa1010')
            ->select(['a1_cod', 'a1_loja', 'a1_nome', 'a1_nreduz', 'a1_vend'])
        // descomentá si usás borrado lógico Protheus:
        // ->where('d_e_l_e_t_', '!=', '*')
        ;

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

    /**
     * POST/GET /api/empresas/buscar
     * Body o query: { codigo: string }
     * Busca en a1_cod, a1_nome, a1_nreduz (ILIKE).
     */
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

        $resultados = DB::connection('ocr_capyc_vendedores')
            ->table('sa1010')
            ->select(['a1_cod', 'a1_loja', 'a1_nome', 'a1_nreduz', 'a1_vend'])
            ->where(function ($query) use ($term) {
                $query->whereRaw('UPPER(TRIM(a1_cod)) ILIKE ?', [$term])
                    ->orWhereRaw('UPPER(TRIM(a1_nome)) ILIKE ?', [$term])
                    ->orWhereRaw('UPPER(TRIM(a1_nreduz)) ILIKE ?', [$term]);
            })
        // ->where('d_e_l_e_t_', '!=', '*') // si aplica
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
}
