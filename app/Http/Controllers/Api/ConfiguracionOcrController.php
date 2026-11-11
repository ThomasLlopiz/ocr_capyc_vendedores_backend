<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionOcr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConfiguracionOcrController extends Controller
{
    /** Listado completo (con paginación opcional). */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 100);
        $perPage = max(1, min($perPage, 200));

        return ConfiguracionOcr::orderByDesc('priority')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /** Formato numérico disponible. */
    public function formatos()
    {
        return response()->json([
            'data' => collect(ConfiguracionOcr::FORMATOS_NUMERICOS)
                ->map(fn($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ]);
    }

    /** Búsqueda de empresas en TOTVS (SA1010). */
    public function buscarEmpresas(Request $request)
    {
        try {
            $q = trim((string) $request->query('q', ''));
            if (mb_strlen($q) < 2) {
                return response()->json(['data' => []]);
            }

            $term = '%' . strtoupper($q) . '%';

            $rows = DB::connection('totvs')
                ->table('sa1010')
                ->select(['a1_filial', 'a1_cod', 'a1_loja', 'a1_nome', 'a1_nreduz', 'a1_cgc', 'a1_vend'])
                ->where('r_e_c_d_e_l_', 0)
                ->where(function ($w) {
                    $w->whereNull('a1_msblql')
                        ->orWhereRaw("TRIM(a1_msblql) <> '1'");
                })
                ->where(function ($query) use ($term) {
                    $query->whereRaw('UPPER(TRIM(a1_cod)) ILIKE ?', [$term])
                        ->orWhereRaw('UPPER(TRIM(a1_nome)) ILIKE ?', [$term])
                        ->orWhereRaw('UPPER(TRIM(a1_nreduz)) ILIKE ?', [$term]);
                })
                ->orderBy('a1_nome')
                ->limit(50)
                ->get();

            return response()->json(['data' => $rows]);
        } catch (\Throwable $e) {
            \Log::error('buscarEmpresas FAIL', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    /** Crear configuración. */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['cliente_codigo_confirmado'] = true;
        $data['version'] = 1;

        $config = ConfiguracionOcr::create($data);

        return response()->json($config, 201);
    }

    /** Actualizar configuración. */
    public function update(Request $request, $id)
    {
        $config = ConfiguracionOcr::findOrFail($id);
        $data = $this->validated($request, $config->id);

        $data['cliente_codigo_confirmado'] = true;

        $config->update($data);
        $config->increment('version');

        return response()->json($config->fresh());
    }

    /** Eliminar configuración. */
    public function destroy($id)
    {
        $config = ConfiguracionOcr::findOrFail($id);
        $config->delete();

        return response()->json(['message' => 'Configuración eliminada']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'boolean',
            'priority' => 'integer|min:0',

            'codigo' => 'nullable|string|max:20',
            'tienda' => 'nullable|string|max:10',
            'filial' => 'nullable|string|max:10',
            'nombre' => 'nullable|string|max:255',
            'nombre_fantasia' => 'nullable|string|max:255',
            'cuit_cuil' => 'nullable|string|max:30',
            'vendedor' => 'nullable|string|max:10',

            'formato_numerico' => [
                'nullable',
                'string',
                \Illuminate\Validation\Rule::in(array_keys(ConfiguracionOcr::FORMATOS_NUMERICOS)),
            ],

            'identificadores_texto' => 'array',
            'identificadores_texto.*' => 'string|max:255',
            'identificadores_ocr' => 'array',
            'identificadores_ocr.*' => 'string|max:255',
            'email_origen' => 'array',
            'email_origen.*' => 'email|max:255',
            'columnas_codigo' => 'required|array|min:1',
            'columnas_codigo.*' => 'string|max:100',
            'columnas_cantidad' => 'required|array|min:1',
            'columnas_cantidad.*' => 'string|max:100',

            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'modo_extraccion' => 'nullable|string|in:codigo_cantidad,cantidad_codigo,custom',
            'linea_regex' => 'nullable|string|max:500|required_if:modo_extraccion,custom',
            'codigo_regex' => 'nullable|string|max:500',
        ]);
    }
}