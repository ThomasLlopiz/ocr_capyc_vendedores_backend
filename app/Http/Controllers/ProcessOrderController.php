<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessOrderController extends Controller
{
    public function createOrder(Request $request)
    {
                                                        // Extract input data
        $clientCode  = $request->input('client_code');  // 000222
        $productCode = $request->input('product_code'); // I30688
        $quantity    = $request->input('quantity');     // 80100
        $store       = $request->input('store');        // 17

        // Start transaction
        DB::beginTransaction();

        try {
            // Fetch client data from sa1010
            $client = DB::table('sa1010')
                ->where('a1_cod', $clientCode)
                ->first();

            if (! $client) {
                return response()->json(['error' => 'Client not found'], 404);
            }

            // Fetch product from sb1010 by b1_cod or b1_xcodcli with TRIM
            $product = DB::table('sb1010')
                ->whereRaw("TRIM(b1_cod) = ?", [$productCode])
                ->orWhereRaw("TRIM(b1_xcodcli) = ?", [$productCode])
                ->first();

            if (! $product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            // Fetch vendor commission from sa3010
            $vendor = DB::table('sa3010')
                ->where('a3_cod', $client->a1_vend)
                ->first();

            // Fetch price from da1010
            $price = DB::table('da1010')
                ->where('da1_codpro', $product->b1_cod)
                ->where('da1_codtab', '')
                ->first();

            // Generate unique order number for c5_filial
            $orderNumber = $this->generateOrderNumber();

            // Insert into sc5010
            $sc5Recno = DB::table('sc5010')->max('r_e_c_n_o_') + 1;
            $sc5Data  = [
                'c5_filial'    => '',
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
                'c5_comis1'    => $vendor->a3_comis ?? 0,
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

            // Insert into sc6010
            $sc6Recno = DB::table('sc6010')->max('r_e_c_n_o_') + 1;
            $boxes    = $product->b1_conv ? ceil($quantity / $product->b1_conv) : 0;
            $sc6Data  = [
                'c6_filial'  => '',
                'c6_item'    => str_pad(DB::table('sc6010')->where('c6_num', $orderNumber)->max('c6_item') + 1, 2, '0', STR_PAD_LEFT),
                'c6_produto' => $product->b1_cod,
                'c6_descri'  => $product->b1_desc,
                'c6_um'      => $product->b1_um,
                'c6_qtdven'  => $quantity,
                'c6_prcven'  => $price->DA1_PRCVEN ?? 0,
                'c6_valor'   => $quantity * ($price->DA1_PRCVEN ?? 0),
                'c6_qtdlib'  => $quantity,
                'c6_segum'   => $product->b1_segum,
                'c6_tes'     => $product->b1_ts,
                'c6_local'   => $product->b1_locpad,
                'c6_cf'      => '612',
                'c6_cli'     => $client->a1_cod,
                'c6_entreg'  => '',
                'c6_loja'    => $store,
                'c6_num'     => $orderNumber,
                'c6_prunit'  => $price->DA1_PRCVEN ?? 0,
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

            // Commit transaction
            DB::commit();

            return response()->json([
                'message'      => 'Order created successfully',
                'order_number' => $orderNumber,
                'sc5010'       => $sc5Data,
                'sc6010'       => $sc6Data,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }

    private function generateOrderNumber()
    {
        // Generate unique c5_num for c5_filial
        $lastOrder = DB::table('sc5010')
            ->where('c5_filial', '')
            ->max('c5_num');
        return $lastOrder ? str_pad((int) $lastOrder + 1, 6, '0', STR_PAD_LEFT) : '000001';
    }
    // En ProcessOrderController.php

    public function updateOrder(Request $request, $orderNumber)
    {
        $data = $request->only([
            'c5_naturez', 'c5_xoccli', 'c5_tabela', 'c5_moeda',
            'c5_tiplib', 'c5_docger', 'c5_xobs',
        ]);

        try {
            DB::table('sc5010')
                ->where('c5_num', $orderNumber)
                ->where('c5_filial', '')
                ->update($data);

            return response()->json(['message' => 'Order updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update order: ' . $e->getMessage()], 500);
        }
    }

    public function updateOrderItem(Request $request, $orderNumber, $item)
    {
        $data = $request->only(['c6_entreg', 'c6_xoccli']);

        try {
            DB::table('sc6010')
                ->where('c6_num', $orderNumber)
                ->where('c6_item', $item)
                ->where('c6_filial', '')
                ->update($data);

            return response()->json(['message' => 'Order item updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update order item: ' . $e->getMessage()], 500);
        }
    }
}
