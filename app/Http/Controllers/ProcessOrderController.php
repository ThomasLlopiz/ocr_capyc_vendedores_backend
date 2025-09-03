<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;       // Agregar esta importación al inicio del archivo
use Illuminate\Support\Facades\DB; // Agregar esta importación al inicio del archivo

class ProcessOrderController extends Controller
{
    public function createOrder(Request $request)
    {
        $clientCode = $request->input('client_code');
        $store      = $request->input('store');
        $filial     = $request->input('filial');
        $articulos  = $request->input('articulos', []);

        if (! $filial) {
            return response()->json(['error' => 'Filial is required'], 400);
        }
        if (empty($articulos)) {
            return response()->json(['error' => 'No articles provided'], 400);
        }

        DB::beginTransaction();
        try {
            $client = DB::table('sa1010')
                ->where('a1_cod', $clientCode)
                ->first();
            if (! $client) {
                return response()->json(['error' => 'Client not found'], 404);
            }

            $orderNumber = $this->generateOrderNumber($filial);
            $sc5Recno    = DB::table('sc5010')->max('r_e_c_n_o_') + 1;
            $sc5Data     = [
                'c5_filial'    => $filial,
                'c5_num'       => $orderNumber,
                'c5_tipo'      => 'N',
                'c5_cliente'   => $client->a1_cod,
                'c5_lojacli'   => $store,
                'c5_client'    => $client->a1_cod,
                'c5_lojaent'   => $client->a1_loja,
                'c5_xnomcli'   => $client->a1_nome,
                'c5_naturez'   => '',
                'c5_tipocli'   => $client->a1_tipo,
                'c5_condpag'   => $client->a1_cond,
                'c5_xoccli'    => '',
                'c5_tabela'    => '',
                'c5_vend1'     => $client->a1_vend,
                'c5_comis1'    => 0,
                'c5_emissao'   => now()->format('Ymd'),
                'c5_moeda'     => '1',
                'c5_mennota'   => '',
                'c5_tiplib'    => '',
                'c5_txmoeda'   => '0',
                'c5_tpcarga'   => '2',
                'c5_docger'    => '',
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
                'c5_xobs'      => '',
            ];
            DB::table('sc5010')->insert($sc5Data);

            $sc6DataList = [];
            $itemNumber  = 1;
            foreach ($articulos as $articulo) {
                $productCode = $articulo['product_code'];
                $quantity    = $articulo['quantity'];

                // Validar cantidad
                if ($quantity <= 0 || $quantity > 1000000) {
                    \Log::warning("Cantidad inválida para {$productCode}: {$quantity}, usando 1");
                    $quantity = 1;
                }

                $product = DB::table('sb1010')
                    ->whereRaw("TRIM(b1_cod) = ?", [$productCode])
                    ->orWhereRaw("TRIM(b1_xcodcli) = ?", [$productCode])
                    ->first();
                if (! $product) {
                    throw new \Exception("Product not found for code: {$productCode}");
                }

                $price = DB::table('da1010')
                    ->where('da1_codpro', $product->b1_cod)
                    ->where('da1_codtab', '')
                    ->first();

                $sc6Recno = DB::table('sc6010')->max('r_e_c_n_o_') + 1;
                $boxes    = $product->b1_conv ? ceil($quantity / $product->b1_conv) : 0;
                $sc6Data  = [
                    'c6_filial'  => $filial,
                    'c6_item'    => str_pad($itemNumber, 2, '0', STR_PAD_LEFT),
                    'c6_produto' => $product->b1_cod,
                    'c6_descri'  => $product->b1_desc,
                    'c6_um'      => $product->b1_um,
                    'c6_qtdven'  => $quantity,
                    'c6_prcven'  => $price->da1_prcven ?? 0,
                    'c6_valor'   => $quantity * ($price->da1_prcven ?? 0),
                    'c6_qtdlib'  => $quantity,
                    'c6_segum'   => $product->b1_segum,
                    'c6_tes'     => $product->b1_ts,
                    'c6_local'   => $product->b1_locpad,
                    'c6_cf'      => '612',
                    'c6_cli'     => $client->a1_cod,
                    'c6_entreg'  => '',
                    'c6_loja'    => $store,
                    'c6_num'     => $orderNumber,
                    'c6_prunit'  => $price->da1_prcven ?? 0,
                    'c6_op'      => '07',
                    'c6_opc'     => '',
                    'c6_tpop'    => 'F',
                    'c6_geranf'  => 'S',
                    'c6_qtdemp'  => $quantity,
                    'c6_qtdemp2' => $boxes,
                    'c6_mopc'    => null,
                    'c6_sugentr' => now()->format('Ymd'),
                    'c6_vdobs'   => null,
                    'c6_rateio'  => '2',
                    'c6_tpprod'  => '1',
                    'c6_provent' => $client->a1_est,
                    'r_e_c_n_o_' => $sc6Recno,
                    'c6_xconv'   => $product->b1_conv,
                    'c6_xcenvli' => $product->b1_xcenvli ?? '',
                    'c6_xcodcli' => $product->b1_xcodcli ?? '',
                    'c6_xoccli'  => '',
                ];
                DB::table('sc6010')->insert($sc6Data);
                $sc6DataList[] = $sc6Data;
                $itemNumber++;
            }

            DB::commit();
            \Log::info('Order created successfully', [
                'client_code'     => $clientCode,
                'store'           => $store,
                'filial'          => $filial,
                'order_number'    => $orderNumber,
                'sc5010_recno'    => $sc5Recno,
                'articulos_count' => count($articulos),
                'sc6010_recnos'   => array_map(fn($item) => $item['r_e_c_n_o_'], $sc6DataList),
            ]);
            return response()->json([
                'message'      => 'Order created successfully',
                'order_number' => $orderNumber,
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
    private function generateOrderNumber($filial)
    {
        // Generate unique c5_num for c5_filial, considering r_e_c_d_e_l_
        $lastOrder = DB::table('sc5010')
            ->where('c5_filial', $filial)
            ->where('r_e_c_d_e_l_', 0) // Considerar solo registros no eliminados
            ->max('c5_num');
        return $lastOrder ? str_pad((int) $lastOrder + 1, 6, '0', STR_PAD_LEFT) : '000001';
    }

    public function updateOrder(Request $request, $orderNumber)
    {
        $data = $request->only([
            'c5_naturez', 'c5_xoccli', 'c5_tabela', 'c5_moeda',
            'c5_tiplib', 'c5_docger', 'c5_xobs',
        ]);

        try {
            $filial = $request->input('filial', '');
            if (! $filial) {
                return response()->json(['error' => 'Filial is required'], 400);
            }

            DB::table('sc5010')
                ->where('c5_num', $orderNumber)
                ->where('c5_filial', $filial)
                ->update($data);

            return response()->json(['message' => 'Order updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update order: ' . $e->getMessage()], 500);
        }
    }

    public function updateOrderItem(Request $request, $orderNumber, $item)
    {
        $data = $request->only([
            'c6_entreg', 'c6_xoccli', 'c6_prunit', 'c6_prcven', 'c6_valor', 'c6_qtdven',
        ]);

        try {
            $filial = $request->input('filial', '');
            if (! $filial) {
                return response()->json(['error' => 'Filial is required'], 400);
            }

            \Log::info("Iniciando actualización de ítem SC6010", [
                'orderNumber' => $orderNumber,
                'item'        => $item,
                'filial'      => $filial,
                'data'        => $data,
            ]);

            $affected = DB::table('sc6010')
                ->where('c6_num', $orderNumber)
                ->where('c6_item', $item)
                ->where('c6_filial', $filial)
                ->update($data);

            if ($affected === 0) {
                \Log::warning("No se actualizó ningún registro en SC6010", [
                    'orderNumber' => $orderNumber,
                    'item'        => $item,
                    'filial'      => $filial,
                ]);
                return response()->json(['error' => 'No se encontró el ítem para actualizar'], 404);
            }

            \Log::info("Actualización de ítem SC6010 completada", [
                'orderNumber' => $orderNumber,
                'item'        => $item,
                'filial'      => $filial,
                'data'        => $data,
            ]);

            return response()->json(['message' => 'Order item updated successfully'], 200);
        } catch (\Exception $e) {
            \Log::error("Error al actualizar ítem SC6010", [
                'orderNumber' => $orderNumber,
                'item'        => $item,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to update order item: ' . $e->getMessage()], 500);
        }
    }
}
