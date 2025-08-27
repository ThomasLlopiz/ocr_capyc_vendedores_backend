<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessOrderController extends Controller
{
    public function createOrder(Request $request)
    {
                                                                                   // Extract input data
        $clientCode      = $request->input('client_code', '000222');               // Default to 000222 for testing
        $productCode     = $request->input('product_code', 'I30556');              // Default to I30556 for testing
        $quantity        = $request->input('quantity', 1);                         // Default quantity
        $userObservation = $request->input('xoccli', '');                          // User-provided observation
        $deliveryDate    = $request->input('delivery_date', now()->format('Ymd')); // Default to current date

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

            // Buscar producto por código, con coincidencias parciales
            $product = DB::table('sb1010')
                ->whereRaw('UPPER(TRIM(b1_cod)) ILIKE ?', ['%' . strtoupper(trim($productCode)) . '%'])
                ->orWhereRaw('UPPER(TRIM(b1_xcodcli)) ILIKE ?', ['%' . strtoupper(trim($productCode)) . '%'])
                ->orderBy('b1_cod', 'ASC')
                ->first();

            if (! $product) {
                return response()->json(['error' => 'Producto no encontrado.'], 404);
            }

            // Fetch vendor commission from sa3010
            $vendor = DB::table('sa3010')
                ->where('a3_cod', $client->a1_vend)
                ->first();

            // Fetch exchange rate if currency is dollar
            $exchangeRate = null;
            $currency     = $request->input('currency', '1'); // Default to peso
            if ($currency == '2') {
                $exchangeRate = DB::table('sm2010')
                    ->where('m2_moeda', '2')
                    ->orderBy('m2_data', 'desc')
                    ->value('m2_moeda2');
            }

                                                                 // Fetch price from da1010
            $priceTable = $request->input('price_table', '001'); // Default table code
            $unitPrice  = DB::table('da1010')
                ->where('da1_codtab', $priceTable)
                ->where('da1_codpro', $productCode)
                ->value('da1_prcven') ?? 0;

            // Generate order number (autonumbered)
            $orderNumber = $this->generateOrderNumber();

            // Insert into sc5010
            $sc5Recno = DB::table('sc5010')->max('r_e_c_n_o_') + 1;
            $sc5Data  = [
                'c5_filial'    => $request->input('filial', ''), // Combobox, empty for now
                'c5_num'       => $orderNumber,
                'c5_tipo'      => 'N',
                'c5_cliente'   => $client->a1_cod,
                'c5_lojacli'   => $client->a1_loja,
                'c5_client'    => $client->a1_cod,
                'c5_lojaent'   => $client->a1_loja,
                'c5_xnomcli'   => $client->a1_nome,
                'c5_naturez'   => $request->input('natureza', ''), // Combobox, empty for now
                'c5_tipocli'   => $client->a1_tipo,
                'c5_condpag'   => $client->a1_cond,
                'c5_xoccli'    => substr($userObservation, 0, 200),
                'c5_tabela'    => $priceTable,
                'c5_vend1'     => $client->a1_vend,
                'c5_comis1'    => $vendor->a3_comis ?? 0,
                'c5_comis2'    => 0,
                'c5_emissao'   => now()->format('Ymd'),
                'c5_moeda'     => $currency,
                'c5_mennota'   => '',
                'c5_tiplib'    => $request->input('tiplib', '1'), // Combobox: 1-item, 2-order
                'c5_txmoeda'   => $exchangeRate ?? 0,
                'c5_tpcarga'   => '2',
                'c5_docger'    => $request->input('docger', '1'), // Combobox: 1-invoice, 2-remito, 3-future
                'c5_gerawms'   => '1',
                'c5_solopc'    => '1',
                'c5_provent'   => $client->a1_est,
                'c5_liqprod'   => '2',
                'c5_idioma'    => '1',
                'c5_paisent'   => $client->a1_pais,
                'c5_tpvent'    => '1',
                'c5_pedecom'   => '',
                'c5_msblql'    => '2',
                'c5_xobs'      => substr($request->input('xobs', ''), 0, 200),
                'r_e_c_n_o_'   => $sc5Recno,
                'r_e_c_d_e_l_' => 0,
            ];
            DB::table('sc5010')->insert($sc5Data);

            // Insert into sc6010
            $sc6Recno = DB::table('sc6010')->max('r_e_c_n_o_') + 1;
            $boxes    = $product->b1_conv ? ceil($quantity / $product->b1_conv) : 0;
            $sc6Data  = [
                'c6_filial'  => $request->input('filial', ''), // Combobox, empty for now
                'c6_item'    => '01',                          // Autonumbered by system
                'c6_produto' => $product->b1_cod,
                'c6_descri'  => $product->b1_desc,
                'c6_um'      => $product->b1_um,
                'c6_qtdven'  => $quantity,
                'c6_prcven'  => $unitPrice,
                'c6_valor'   => $quantity * $unitPrice,
                'c6_qtdlib'  => $quantity,
                'c6_segum'   => $product->b1_segum,
                'c6_tes'     => $product->b1_ts,
                'c6_local'   => $product->b1_locpad,
                'c6_cf'      => '612',
                'c6_cli'     => $client->a1_cod,
                'c6_entreg'  => $deliveryDate,
                'c6_loja'    => $client->a1_loja,
                'c6_num'     => $orderNumber,
                'c6_prunit'  => $unitPrice,
                'c6_op'      => '07',
                'c6_opc'     => '',
                'c6_tpop'    => 'F',
                'c6_geranf'  => $sc5Data['c5_docger'] == '2' ? 'N' : 'S',
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
                'c6_xcenvli' => $product->b1_xcenvli,
                'c6_xcodcli' => $product->b1_xcodcli ?? '',
                'c6_xoccli'  => substr($userObservation, 0, 200),
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
        // Simple autonumber logic (adjust as per your system)
        $lastOrder = DB::table('sc5010')->max('c5_num');
        $newNumber = $lastOrder ? str_pad((int) $lastOrder + 1, 6, '0', STR_PAD_LEFT) : '000001';
        return $newNumber;
    }
}
