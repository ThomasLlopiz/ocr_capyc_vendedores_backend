<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProcessOrderController;
use App\Models\DocumentoOcr;
use App\Services\DespachadorOcr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EspacioTrabajoController extends Controller
{
    // ============================================================
    // HELPERS
    // ============================================================

    private function role(Request $request): string
    {
        return (string) ($request->user()->role ?? 'user');
    }

    private function staff(Request $request): void
    {
        abort_unless(in_array($this->role($request), ['admin', 'operador'], true), 403);
    }

    private function codes(Request $request): array
    {
        $raw = $request->user()->code;
        return array_map(
            fn($code) => str_pad(trim((string) $code), 6, '0', STR_PAD_LEFT),
            is_array($raw) ? $raw : ($raw ? [$raw] : [])
        );
    }

    private function canView(Request $request, DocumentoOcr $document): void
    {
        if (in_array($this->role($request), ['admin', 'operador'], true)) {
            return;
        }

        $seller = (string) $document->assigned_seller_code;
        abort_unless(
            $this->role($request) === 'vendedor'
            && $document->seller_portfolio
            && in_array($seller, $this->codes($request), true),
            403,
            'No autorizado'
        );
    }

    // ============================================================
    // LISTADO
    // ============================================================

    public function documents(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:pending,queued,processing,review,accepted,failed',
            'scope' => 'nullable|in:ocr,seller',
        ]);

        $role = $this->role($request);
        $scope = $filters['scope'] ?? 'ocr';

        $query = DocumentoOcr::query()->latest();

        if (filled($filters['q'] ?? null)) {
            $query->where('original_name', 'like', '%' . $filters['q'] . '%');
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if ($scope === 'seller') {
            abort_unless(in_array($role, ['admin', 'vendedor'], true), 403);

            if ($role === 'vendedor') {
                $codes = $this->codes($request);
                if (empty($codes)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where('seller_portfolio', true)
                        ->whereIn('assigned_seller_code', $codes);
                }
            } else {
                $query->where('seller_portfolio', true);
            }
        } else {
            abort_unless(in_array($role, ['admin', 'operador'], true), 403);
        }

        return $query->paginate(20);
    }

    public function showDocument(Request $request, DocumentoOcr $document)
    {
        $this->canView($request, $document);
        return $document;
    }

    // ============================================================
    // EDICIÓN
    // ============================================================

    public function saveDocument(Request $request, DocumentoOcr $document)
    {
        $this->staff($request);
        abort_unless($document->status === 'review', 409, 'Solo se editan documentos en revisión');

        $data = $request->validate([
            'empresa' => 'nullable|string|max:255',
            'codigo' => 'required|string|max:30',
            'tienda' => 'nullable|string|max:9',
            'filial' => 'nullable|string|max:9',
            'vendedor' => 'required|string|max:6',
            'oc_cliente' => 'nullable|string|max:90',
            'articulos' => 'required|array|min:1',
            'diagnostico' => 'nullable|array',
        ]);

        foreach ($data['articulos'] as $item) {
            abort_unless(
                is_array($item)
                && filled($item['Codigo'] ?? null)
                && strlen((string) $item['Codigo']) <= 40
                && is_numeric($item['cant.'] ?? null)
                && (float) $item['cant.'] > 0,
                422,
                'Código o cantidad inválidos'
            );
        }

        $previous = $document->extracted['articulos'] ?? [];
        foreach ($data['articulos'] as $index => &$item) {
            $original = $previous[$index] ?? [];
            $sameCode = trim((string) ($original['Codigo'] ?? '')) === trim((string) $item['Codigo']);

            foreach ([
                'b1_desc',
                'b1_conv',
                'b1_cxxpl',
                'b1_um',
                'b1_segum',
                'b1_xcenvli',
                'b1_ts',
                'b1_locpad',
                'b1_xcodcli',
                'b1_cod',
                '_found',
            ] as $field) {
                unset($item[$field]);
                if ($sameCode && array_key_exists($field, $original)) {
                    $item[$field] = $original[$field];
                }
            }

            if (!$sameCode) {
                $item['_found'] = 'unverified';
            }
        }
        unset($item);

        $data['vendedor'] = str_pad($data['vendedor'], 6, '0', STR_PAD_LEFT);

        $document->update([
            'extracted' => array_merge($document->extracted ?? [], $data),
        ]);

        return $document->fresh();
    }

    public function saveSellerItems(Request $request, DocumentoOcr $document)
    {
        $this->canView($request, $document);
        abort_unless(
            in_array($this->role($request), ['admin', 'vendedor'], true)
            && in_array($document->status, ['accepted', 'review'], true),
            403
        );

        $data = $request->validate([
            'articulos' => 'required|array|min:1',
        ]);

        foreach ($data['articulos'] as $item) {
            abort_unless(
                is_array($item)
                && filled($item['Codigo'] ?? null)
                && strlen((string) $item['Codigo']) <= 40
                && is_numeric($item['cant.'] ?? null)
                && (float) $item['cant.'] > 0,
                422,
                'Código o cantidad inválidos'
            );
        }

        $extracted = $document->extracted;
        abort_unless(
            count($data['articulos']) === count($extracted['articulos'] ?? []),
            422,
            'Solo se pueden corregir artículos existentes'
        );

        foreach ($data['articulos'] as $index => $input) {
            $original = $extracted['articulos'][$index];

            if (trim((string) $original['Codigo']) !== trim((string) $input['Codigo'])) {
                foreach ([
                    'b1_desc',
                    'b1_conv',
                    'b1_cxxpl',
                    'b1_um',
                    'b1_segum',
                    'b1_xcenvli',
                    'b1_ts',
                    'b1_locpad',
                    'b1_xcodcli',
                    'b1_cod',
                ] as $key) {
                    unset($original[$key]);
                }
                $original['_found'] = 'unverified';
            }

            $original['Codigo'] = trim((string) $input['Codigo']);
            $original['cant.'] = (string) $input['cant.'];
            $extracted['articulos'][$index] = $original;
        }

        $document->update(['extracted' => $extracted]);

        return $document->fresh();
    }

    // ============================================================
    // ACEPTAR
    // ============================================================

    public function accept(Request $request, DocumentoOcr $document)
    {
        $this->staff($request);
        abort_unless($document->status === 'review', 409, 'Solo se aceptan documentos en revisión');

        $data = $document->extracted ?? [];

        abort_unless(
            !empty($data['codigo'])
            && filled($data['filial'] ?? null)
            && ($data['filial'] ?? '') !== '0'
            && !empty($data['vendedor'])
            && ($data['vendedor'] ?? '') !== '0'
            && !empty($data['articulos']),
            422,
            'Código, filial, vendedor o artículos faltantes'
        );

        $data['vendedor'] = str_pad(trim((string) $data['vendedor']), 6, '0', STR_PAD_LEFT);

        $document->update([
            'status' => 'accepted',
            'extracted' => $data,
            'seller_portfolio' => true,
            'assigned_seller_code' => $data['vendedor'],
        ]);

        return $document->fresh();
    }

    public function acceptBatch(Request $request)
    {
        $this->staff($request);

        $data = $request->validate([
            'document_ids' => 'required|array|min:1|max:50',
            'document_ids.*' => 'required|integer|distinct',
        ]);

        return DB::transaction(function () use ($data) {
            $documents = DocumentoOcr::whereIn('id', $data['document_ids'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            abort_unless($documents->count() === count($data['document_ids']), 422, 'Falta un documento');

            foreach ($documents as $document) {
                abort_unless($document->status === 'review', 409, 'Solo se aceptan en revisión');

                $value = $document->extracted ?? [];
                abort_unless(
                    filled($value['codigo'] ?? null)
                    && filled($value['filial'] ?? null)
                    && ($value['filial'] ?? '') !== '0'
                    && filled($value['vendedor'] ?? null)
                    && ($value['vendedor'] ?? '') !== '0'
                    && !empty($value['articulos']),
                    422,
                    'Código, filial, vendedor o artículos faltantes'
                );
            }

            foreach ($documents as $document) {
                $value = $document->extracted;
                $value['vendedor'] = str_pad(trim((string) $value['vendedor']), 6, '0', STR_PAD_LEFT);
                $document->update([
                    'status' => 'accepted',
                    'extracted' => $value,
                    'seller_portfolio' => true,
                    'assigned_seller_code' => $value['vendedor'],
                ]);
            }

            return ['accepted' => $documents->pluck('id')];
        });
    }

    // ============================================================
    // REPROCESAR
    // ============================================================

    public function retry(Request $request, DocumentoOcr $document)
    {
        if ($this->role($request) === 'vendedor') {
            $this->canView($request, $document);
        } else {
            $this->staff($request);
        }

        app(DespachadorOcr::class)->procesar($document);

        return $document->fresh();
    }

    public function retryBatch(Request $request)
    {
        $ids = $request->validate([
            'document_ids' => 'required|array|min:1|max:50',
            'document_ids.*' => 'required|integer|distinct',
        ])['document_ids'];

        $results = [];
        $despachador = app(DespachadorOcr::class);

        foreach ($ids as $id) {
            $document = DocumentoOcr::find($id);
            if (!$document) {
                $results[] = ['id' => $id, 'status' => 'failed', 'message' => 'Documento no encontrado'];
                continue;
            }

            try {
                if ($this->role($request) === 'vendedor') {
                    $this->canView($request, $document);
                } else {
                    $this->staff($request);
                }

                $despachador->procesar($document);

                $results[] = [
                    'id' => $id,
                    'name' => $document->original_name,
                    'status' => $document->status,
                ];
            } catch (\Throwable $e) {
                $forbidden = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                    && $e->getStatusCode() === 403;

                $results[] = [
                    'id' => $id,
                    'name' => $forbidden ? null : $document->original_name,
                    'status' => 'failed',
                    'message' => $forbidden
                        ? 'No autorizado'
                        : ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                            ? $e->getMessage()
                            : 'No se pudo reprocesar el documento'),
                ];
            }
        }

        return ['results' => $results];
    }

    // ============================================================
    // HISTORIAL
    // ============================================================

    public function history(Request $request, DocumentoOcr $document)
    {
        $this->canView($request, $document);

        return $document->ejecuciones()
            ->select(['id', 'status', 'previous_status', 'previous_extracted', 'config_hash', 'error', 'created_at'])
            ->get();
    }

    // ============================================================
    // PDF
    // ============================================================

    public function pdf(Request $request, DocumentoOcr $document)
    {
        $this->canView($request, $document);

        $path = $document->pdfAbsolutePath();
        abort_unless(is_file($path), 404, 'PDF no encontrado');

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
        ]);
    }

    // ============================================================
    // VERIFICAR PRODUCTOS (SB1010)
    // ============================================================

    public function verifyProducts(Request $request, DocumentoOcr $document)
    {
        $this->canView($request, $document);
        abort_unless(in_array($document->status, ['review', 'accepted'], true), 409);

        $data = $document->extracted ?? [];
        $data['articulos'] = $data['articulos'] ?? [];

        foreach ($data['articulos'] as &$item) {
            $code = trim((string) ($item['Codigo'] ?? ''));

            $product = DB::connection('totvs')->table('sb1010')
                ->select('b1_cod', 'b1_desc', 'b1_um', 'b1_segum', 'b1_xcenvli', 'b1_conv', 'b1_cxxpl', 'b1_ts', 'b1_locpad', 'b1_xcodcli')
                ->where('r_e_c_d_e_l_', 0)
                ->where(function ($q) use ($code) {
                    $q->whereRaw('TRIM(b1_cod) = ?', [$code])
                        ->orWhereRaw('TRIM(b1_xcodcli) = ?', [$code]);
                })
                ->first();

            $item['_found'] = $product ? 'ok' : 'missing';

            if ($product) {
                foreach ((array) $product as $key => $value) {
                    $item[$key] = trim((string) $value);
                }
            }
        }
        unset($item);

        $document->update(['extracted' => $data]);

        return $document->fresh();
    }

    // ============================================================
    // CREAR PEDIDO → delega en ProcessOrderController::createOrder
    // ============================================================

    public function createOrders(Request $request, DocumentoOcr $document)
    {
        $this->canView($request, $document);
        abort_unless(in_array($this->role($request), ['admin', 'vendedor'], true), 403);
        abort_unless($document->status === 'accepted', 409, 'El documento debe estar aceptado');

        $extracted = $document->extracted ?? [];

        abort_if(empty($extracted['articulos']), 422, 'El documento no tiene artículos');
        abort_if(empty($extracted['codigo']), 422, 'Falta código de cliente');
        abort_if(empty($extracted['filial']), 422, 'Falta filial. Completala antes de crear el pedido.');
        abort_if(empty($extracted['vendedor']), 422, 'Falta vendedor');

        // Naturaleza según tienda
        $tienda = trim((string) ($extracted['tienda'] ?? ''));
        $naturez = match ($tienda) {
            '18' => 'ENVASES',
            '17' => 'FLEXO',
            default => 'ENVASES',
        };

        // Adaptar articulos al shape de ProcessOrderController::createOrder
        $articulosPayload = [];
        foreach ($extracted['articulos'] as $art) {
            $qty = (int) round((float) ($art['cant.'] ?? 0));
            if ($qty <= 0) {
                continue;
            }

            $articulosPayload[] = [
                'product_code' => trim((string) ($art['Codigo'] ?? '')),
                'quantity' => $qty,
                'c6_prcven' => $art['c6_prcven'] ?? null,
                'c6_prunit' => $art['c6_prunit'] ?? null,
                'PrcLista' => $art['PrcLista'] ?? null,
                'c6_descri' => $art['b1_desc'] ?? null,
                'c6_um' => $art['b1_um'] ?? null,
                'c6_segum' => $art['b1_segum'] ?? null,
                'c6_tes' => $art['b1_ts'] ?? null,
                'c6_local' => $art['b1_locpad'] ?? null,
                'c6_xconv' => $art['b1_conv'] ?? null,
                'c6_xcenvli' => $art['b1_xcenvli'] ?? null,
                'c6_xcodcli' => $art['b1_xcodcli'] ?? null,
            ];
        }

        abort_if(empty($articulosPayload), 422, 'No hay artículos con cantidad válida');

        $body = [
            'client_code' => trim((string) $extracted['codigo']),
            'store' => $tienda ?: '01',
            'filial' => trim((string) $extracted['filial']),
            'c5_vend1' => str_pad(trim((string) $extracted['vendedor']), 6, '0', STR_PAD_LEFT),
            'oc_cliente' => trim((string) ($extracted['oc_cliente'] ?? '')),
            'c5_naturez' => $naturez,
            'c5_tabela' => null,
            'c5_moeda' => 1,
            'c5_tiplib' => '1',
            'c5_docger' => '1',
            'c5_xobs' => '',
            'c5_xpdf' => $document->original_name,
            'c5_tes' => '1',
            'articulos' => $articulosPayload,
        ];

        // Delegar a ProcessOrderController (una sola fuente de verdad para SC5/SC6)
        $delegate = Request::create('/api/create-order', 'POST', $body);
        $response = app(ProcessOrderController::class)->createOrder($delegate);

        // Guardar trace de los c5_num generados
        if ($response->getStatusCode() === 201) {
            $payload = $response->getData(true);
            $extracted['_created_orders'] = $extracted['_created_orders'] ?? [];
            $extracted['_created_orders'][] = [
                'c5_num' => $payload['order_number'] ?? null,
                'created_at' => now()->toIso8601String(),
                'created_by' => $request->user()->id,
            ];
            $document->update(['extracted' => $extracted]);
        }

        return $response;
    }

    // ============================================================
    // ELIMINAR
    // ============================================================

    public function deleteDocument(Request $request, DocumentoOcr $document)
    {
        $this->staff($request);

        abort_if(
            in_array($document->status, ['queued', 'processing'], true),
            409,
            'El documento tiene un trabajo activo'
        );

        $document->delete();

        return ['message' => 'Registro eliminado; el PDF se conservó'];
    }

    public function deleteExtraction(Request $request, DocumentoOcr $document)
    {
        $this->staff($request);

        abort_if(
            in_array($document->status, ['queued', 'processing'], true),
            409,
            'El documento tiene un trabajo activo'
        );

        $document->update([
            'extracted' => null,
            'status' => 'pending',
            'error' => null,
        ]);

        return $document->fresh();
    }
}