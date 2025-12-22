<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessOrderController extends Controller
{
    public function createOrder(Request $request)
    {
        // --------- ENTRADA BÁSICA ---------
        $clientCode = $request->input('client_code');
        $store      = $request->input('store');
        $filial     = $request->input('filial');
        $articulos  = $request->input('articulos', []);
        $ocCliente  = trim((string) $request->input('oc_cliente', ''));

        if (
            $ocCliente === '' ||
            $ocCliente === '0' ||
            $ocCliente === '000000' ||
            strtoupper($ocCliente) === 'OCC-000000'
        ) {
            $ocCliente = $this->generarOcCliente();
        }
        if (! $filial) {
            return response()->json(['error' => 'Filial is required'], 400);
        }
        if (empty($articulos)) {
            return response()->json(['error' => 'No articles provided'], 400);
        }
        $c5_naturez = trim((string) $request->input('c5_naturez', 'ENVASES'));
        $c5_tabela  = trim((string) $request->input('c5_tabela', '151'));
        $c5_moeda   = trim((string) $request->input('c5_moeda', '1'));
        $c5_tiplib  = trim((string) $request->input('c5_tiplib', '1'));
        $c5_docger  = trim((string) $request->input('c5_docger', '1'));
        $c5_xobs    = trim((string) $request->input('c5_xobs', ''));

        DB::connection('ocr_capyc_vendedores')->beginTransaction();
        try {
            // --------- CLIENTE ----------
            $client = DB::connection('totvs')
                ->table('sa1010')
                ->where('a1_cod', $clientCode)
                ->first();
            if (! $client) {
                return response()->json(['error' => 'Client not found'], 404);
            }

            // --------- SC5 (HEADER) ----------
            $orderNumber = $this->generateOrderNumber($filial);
            $sc5Recno    = DB::connection('ocr_capyc_vendedores')
                ->table('sc5010')->max('r_e_c_n_o_') + 1;

            $c5_xpdf = $request->input('c5_xpdf');

            $sc5Data = [
                'c5_filial'    => $filial,
                'c5_num'       => $orderNumber,
                'c5_tipo'      => 'N',
                'c5_cliente'   => $client->a1_cod,
                'c5_lojacli'   => $store,
                'c5_client'    => $client->a1_cod,
                'c5_lojaent'   => $client->a1_loja,
                'c5_xnomcli'   => $client->a1_nome,
                'c5_naturez'   => $c5_naturez,
                'c5_tabela'    => $c5_tabela,
                'c5_moeda'     => $c5_moeda,
                'c5_tiplib'    => $c5_tiplib,
                'c5_docger'    => $c5_docger,
                'c5_xobs'      => $c5_xobs,
                'c5_tipocli'   => $client->a1_tipo,
                'c5_condpag'   => $client->a1_cond,
                'c5_xoccli'    => $ocCliente,
                'c5_emissao'   => now()->format('Ymd'),
                'c5_xpdf'      => $c5_xpdf,
                'c5_txmoeda'   => '0',
                'c5_tpcarga'   => '2',
                'c5_gerawms'   => '1',
                'c5_solopc'    => '1',
                'c5_provent'   => $client->a1_est,
                'c5_liqprod'   => '2',
                'c5_idioma'    => '1',
                'c5_paisent'   => $client->a1_pais,
                'c5_tpvent'    => '1',
                'c5_pedecom'   => '',
                'c5_msblql'    => '2',
                'r_e_c_n_o_'   => $sc5Recno,
                'r_e_c_d_e_l_' => 0,
            ];
            DB::connection('ocr_capyc_vendedores')
                ->table('sc5010')->insert($sc5Data);
            // --------- SC6 (ITENS) ----------
            $sc6DataList = [];
            $itemNumber  = 1;

            foreach ($articulos as $art) {
                $productCodeRaw = $art['product_code'] ?? '';
                $productCode    = trim((string) $productCodeRaw);
                $quantityRaw    = $art['quantity'] ?? 0;
                $quantity       = (int) $quantityRaw;

                if ($quantity <= 0 || $quantity > 1000000) {
                    \Log::warning("Cantidad inválida para {$productCode}: {$quantity}, usando 1");
                    $quantity = 1;
                }

                $product = DB::connection('totvs')
                    ->table('sb1010')
                    ->where(function ($q) use ($productCode) {
                        $q->whereRaw("TRIM(b1_cod) = ?", [$productCode])
                            ->orWhereRaw("TRIM(b1_xcodcli) = ?", [$productCode]);
                    })
                    ->where('b1_msblql', '<>', 1)
                    ->first();

                if (! $product) {
                    throw new \Exception("Product not found for code: {$productCode}");
                }

                $prcFront = $art['c6_prcven'] ?? $art['c6_prunit'] ?? $art['PrcLista'] ?? null;
                $priceRow = null;
                if ($prcFront === null) {
                    $priceRow = DB::connection('totvs')
                        ->table('da1010')
                        ->where('da1_codpro', $product->b1_cod)
                        ->where('da1_codtab', $c5_tabela)
                        ->first();

                }

                $unitPrice = $prcFront !== null ? floatval($prcFront)
                    : floatval($priceRow->da1_prcven ?? 0);

                $entreg = trim((string) ($art['c6_entreg'] ?? ''));
                if ($entreg === '') {
                    $entreg = now()->addDays(30)->format('Ymd');
                }

                $sc6Recno = DB::connection('ocr_capyc_vendedores')
                    ->table('sc6010')->max('r_e_c_n_o_') + 1;
                $boxes = array_key_exists('c6_qtdemp2', $art)
                    ? (int) $art['c6_qtdemp2']
                    : ($product->b1_conv ? (int) ceil($quantity / $product->b1_conv) : 0);

                $sc6Data = [
                    'c6_filial'  => $filial,
                    'c6_item'    => str_pad($itemNumber, 2, '0', STR_PAD_LEFT),
                    'c6_produto' => $product->b1_cod,
                    'c6_descri'  => $art['c6_descri'] ?? $product->b1_desc,
                    'c6_um'      => $art['c6_um'] ?? $product->b1_um,
                    'c6_qtdven'  => $quantity,
                    'c6_prcven'  => $unitPrice,
                    'c6_valor'   => $quantity * $unitPrice,
                    'c6_qtdlib'  => $quantity,
                    'c6_segum'   => $art['c6_segum'] ?? $product->b1_segum,
                    'c6_tes'     => $art['c6_tes'] ?? $product->b1_ts,
                    'c6_local'   => $art['c6_local'] ?? $product->b1_locpad,
                    'c6_cf'      => '612',
                    'c6_cli'     => $client->a1_cod,
                    'c6_entreg'  => $entreg,
                    'c6_loja'    => $store,
                    'c6_num'     => $orderNumber,
                    'c6_prunit'  => $unitPrice,
                    'c6_op'      => '07',
                    'c6_opc'     => '',
                    'c6_tpop'    => 'F',
                    'c6_geranf'  => 'S',
                    'c6_qtdemp'  => $quantity,
                    'c6_qtdemp2' => $boxes,
                    'c6_mopc'    => null,
                    'c6_sugentr' => $entreg,
                    'c6_vdobs'   => null,
                    'c6_rateio'  => '2',
                    'c6_tpprod'  => '1',
                    'c6_provent' => $client->a1_est,
                    'r_e_c_n_o_' => $sc6Recno,
                    'c6_xconv'   => $art['c6_xconv'] ?? $product->b1_conv,
                    'c6_xcenvli' => $art['c6_xcenvli'] ?? ($product->b1_xcenvli ?? ''),
                    'c6_xcodcli' => $art['c6_xcodcli'] ?? ($product->b1_xcodcli ?? ''),
                    'c6_xoccli'  => $ocCliente,
                ];

                DB::connection('ocr_capyc_vendedores')
                    ->table('sc6010')->insert($sc6Data);
                $sc6DataList[] = $sc6Data;
                $itemNumber++;
            }

            DB::connection('ocr_capyc_vendedores')->commit();

            \Log::info('Order created successfully', [
                'client_code'     => $clientCode,
                'store'           => $store,
                'filial'          => $filial,
                'order_number'    => $orderNumber,
                'sc5010_recno'    => $sc5Recno,
                'articulos_count' => count($articulos),
                'sc6010_recnos'   => array_map(fn($i) => $i['r_e_c_n_o_'], $sc6DataList),
            ]);

            return response()->json([
                'message'      => 'Order created successfully',
                'order_number' => $orderNumber,
                'pdf_file'     => $c5_xpdf,
                'sc5010'       => $sc5Data,
                'sc6010'       => $sc6DataList,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create order', [
                'client_code' => $clientCode,
                'store'       => $store,
                'filial'      => $filial,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }
    private function generarOcCliente(): string
    {
        $last = DB::connection('ocr_capyc_vendedores')
            ->table('sc5010')
            ->whereNotNull('c5_xoccli')
            ->where('c5_xoccli', 'like', 'OCC-%')
            ->orderByRaw("CAST(SUBSTRING(c5_xoccli, 5, 6) AS INTEGER) DESC")
            ->value('c5_xoccli');

        if (! $last) {
            return 'OCC-000001';
        }

        preg_match('/OCC-(\d+)/', $last, $m);
        $next = isset($m[1]) ? ((int) $m[1] + 1) : 1;

        return 'OCC-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function generateOrderNumber($filial)
    {
        $maxTotvs = DB::connection('totvs')
            ->table('sc5010')
            ->where('c5_filial', $filial)
            ->where('r_e_c_d_e_l_', 0)
            ->max(DB::raw("CAST(c5_num AS INTEGER)"));

        $maxLocal = DB::connection('ocr_capyc_vendedores')
            ->table('sc5010')
            ->where('c5_filial', $filial)
            ->where('r_e_c_d_e_l_', 0)
            ->max(DB::raw("CAST(c5_num AS INTEGER)"));

        $maxTotvs = (int) $maxTotvs;
        $maxLocal = (int) $maxLocal;
        $max      = max($maxTotvs, $maxLocal);
        $next     = $max + 1;
        return str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function updateOrder(Request $request, $orderNumber)
    {
        \Log::info('🔹 [updateOrder] Iniciando actualización de SC5010', [
            'orderNumber'  => $orderNumber,
            'request_data' => $request->all(),
        ]);

        $filial = $request->input('filial') ?? $request->input('c5_filial');

        if (! $filial) {
            $row = \App\Models\Sc5010::where('c5_num', $orderNumber)->first();
            if ($row) {
                $filial = $row->c5_filial;
            }
        }

        if (! $filial) {
            \Log::warning('⚠️ [updateOrder] Falta filial', [
                'orderNumber' => $orderNumber,
                'data'        => $request->all(),
            ]);
            return response()->json(['message' => 'Filial requerida'], 422);
        }

        $sc5010 = \App\Models\Sc5010::where('c5_num', $orderNumber)
            ->where('c5_filial', $filial)
            ->firstOrFail();

        $payload = $request->validate([
            'estado_ocs' => 'integer|min:0|max:9|nullable',
            'c5_naturez' => 'string|nullable',
            'c5_tabela'  => 'string|nullable',
            'c5_moeda'   => 'string|nullable',
            'c5_tiplib'  => 'string|nullable',
            'c5_docger'  => 'string|nullable',
            'c5_xobs'    => 'string|nullable',
            'c5_xoccli'  => 'string|nullable',
            'c5_condpag' => 'string|nullable',
            'c5_tipocli' => 'string|nullable',
        ]);

        \DB::connection('ocr_capyc_vendedores')
            ->table('sc5010')
            ->where('c5_num', $orderNumber)
            ->where('c5_filial', $filial)
            ->update($payload);
        $sc5010 = \App\Models\Sc5010::where('c5_num', $orderNumber)
            ->where('c5_filial', $filial)
            ->first();

        return response()->json([
            'message' => 'Order updated successfully',
            'data'    => $sc5010,
        ], 200);
    }

    public function updateOrderItem(Request $request, $orderNumber, $item)
    {
        $data = $request->only([
            'c6_entreg',
            'c6_xoccli',
            'c6_prunit',
            'c6_prcven',
            'c6_valor',
            'c6_qtdven',
            'c6_tes',
        ]);

        if (array_key_exists('c6_xoccli', $data)) {
            $xoccli = $data['c6_xoccli'];
            $xoccli = is_string($xoccli) ? trim($xoccli) : $xoccli;

            if ($xoccli === '' || is_null($xoccli)) {
                unset($data['c6_xoccli']);
            } else {
                $data['c6_xoccli'] = $xoccli;
            }
        }

        if (empty($data['c6_entreg'])) {
            $data['c6_entreg'] = now()->addDays(30)->format('Ymd');
        }

        try {
            $filial = $request->input('filial', '');
            if (! $filial) {
                $filial = DB::connection('ocr_capyc_vendedores')
                    ->table('sc6010')
                    ->where('c6_num', $orderNumber)
                    ->value('c6_filial');
            }
            \Log::info("Iniciando actualización de ítem SC6010", [
                'orderNumber' => $orderNumber,
                'item'        => $item,
                'filial'      => $filial,
                'data'        => $data,
            ]);

            $affected = DB::connection('ocr_capyc_vendedores')
                ->table('sc6010')
                ->where('c6_num', $orderNumber)
                ->where('c6_item', $item)
                ->where('c6_filial', $filial)
                ->update($data);

            if ($affected === 0) {
                \Log::warning("No se actualizó ningún registro en SC6010", [
                    'orderNumber' => $orderNumber,
                    'item'        => $item,
                    'filial'      => $filial,
                    'data'        => $data,
                ]);
                return response()->json(['error' => 'No se encontró el ítem para actualizar'], 404);
            }

            \Log::info("✅ Actualización de ítem SC6010 completada", [
                'orderNumber' => $orderNumber,
                'item'        => $item,
                'filial'      => $filial,
                'data'        => $data,
            ]);

            return response()->json(['message' => 'Order item updated successfully'], 200);
        } catch (\Exception $e) {
            \Log::error("❌ Error al actualizar ítem SC6010", [
                'orderNumber' => $orderNumber,
                'item'        => $item,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to update order item: ' . $e->getMessage()], 500);
        }
    }

}
