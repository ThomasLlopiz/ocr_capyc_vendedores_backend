<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sc6010', function (Blueprint $table) {
            $table->id();

            // VARCHAR
            $table->string('c6_filial', 9)->default('      ');
            $table->string('c6_item', 9)->default('  ');
            $table->string('c6_produto', 95)->default('               ');
            $table->string('c6_descri', 95)->default('                                                                 ');
            $table->string('c6_um', 9)->default('  ');

            // NUMÉRICOS
            $table->double('c6_qtdven')->default(0.0);
            $table->double('c6_prcven')->default(0.0);
            $table->double('c6_valor')->default(0.0);
            $table->double('c6_qtdlib')->default(0.0);
            $table->string('c6_segum', 9)->default('  ');
            $table->double('c6_qtdlib2')->default(0.0);
            $table->string('c6_tes', 9)->default('   ');
            $table->double('c6_unsven')->default(0.0);
            $table->string('c6_local', 9)->default('  ');
            $table->double('c6_qtdent')->default(0.0);
            $table->double('c6_qtdent2')->default(0.0);
            $table->string('c6_cf', 9)->default('     ');
            $table->string('c6_cli', 9)->default('      ');
            $table->double('c6_descont')->default(0.0);
            $table->double('c6_valdesc')->default(0.0);
            $table->string('c6_la', 9)->default('        ');
            $table->string('c6_entreg', 9)->default('        ');
            $table->string('c6_loja', 9)->default('  ');
            $table->string('c6_nota', 92)->default('            ');
            $table->string('c6_datfat', 9)->default('        ');
            $table->string('c6_serie', 9)->default('   ');
            $table->string('c6_num', 9)->default('      ');
            $table->double('c6_comis1')->default(0.0);
            $table->double('c6_comis2')->default(0.0);
            $table->double('c6_comis3')->default(0.0);
            $table->double('c6_comis4')->default(0.0);
            $table->double('c6_comis5')->default(0.0);
            $table->string('c6_pedcli', 9)->default('         ');
            $table->double('c6_prunit')->default(0.0);
            $table->string('c6_bloquei', 9)->default('  ');
            $table->string('c6_reserva', 9)->default('      ');
            $table->string('c6_op', 9)->default('  ');
            $table->string('c6_ok', 9)->default('  ');
            $table->string('c6_nfori', 92)->default('            ');
            $table->string('c6_seriori', 9)->default('   ');
            $table->string('c6_itemori', 9)->default('    ');
            $table->double('c6_ipidev')->default(0.0);
            $table->string('c6_identb6', 9)->default('      ');
            $table->string('c6_blq', 9)->default('  ');
            $table->double('c6_picmret')->default(0.0);
            $table->string('c6_codiss', 9)->default('         ');
            $table->string('c6_grade', 9)->default(' ');
            $table->string('c6_itemgrd', 9)->default('   ');
            $table->string('c6_lotectl', 90)->default('          ');
            $table->string('c6_numlote', 9)->default('      ');
            $table->string('c6_dtvalid', 9)->default('        ');
            $table->string('c6_numorc', 9)->default('        ');
            $table->string('c6_chassi', 95)->default('                         ');
            $table->string('c6_opc', 90)->default('                                                                                ');
            $table->string('c6_localiz', 95)->default('               ');
            $table->string('c6_numseri', 90)->default('                    ');
            $table->string('c6_numop', 9)->default('      ');
            $table->string('c6_itemop', 9)->default('  ');
            $table->string('c6_clasfis', 9)->default('   ');
            $table->double('c6_qtdrese')->default(0.0);
            $table->string('c6_contrat', 9)->default('      ');
            $table->string('c6_numos', 94)->default('              ');
            $table->string('c6_numosfa', 94)->default('              ');
            $table->string('c6_codfab', 9)->default('      ');
            $table->string('c6_lojafa', 9)->default('  ');
            $table->string('c6_itemcon', 9)->default('  ');
            $table->string('c6_tpop', 9)->default(' ');
            $table->string('c6_revisao', 9)->default('   ');
            $table->string('c6_geranf', 9)->default(' ');
            $table->string('c6_locdest', 9)->default('  ');
            $table->string('c6_servic', 9)->default('   ');
            $table->string('c6_endpad', 95)->default('               ');
            $table->string('c6_tpestr', 9)->default('      ');
            $table->string('c6_contrt', 95)->default('               ');
            $table->string('c6_tpcontr', 9)->default(' ');
            $table->string('c6_itcontr', 9)->default('  ');
            $table->string('c6_geroupv', 9)->default(' ');
            $table->string('c6_projpms', 90)->default('          ');
            $table->string('c6_edtpms', 92)->default('            ');
            $table->string('c6_taskpms', 92)->default('            ');
            $table->string('c6_trt', 9)->default('   ');
            $table->double('c6_qtdemp')->default(0.0);
            $table->double('c6_qtdemp2')->default(0.0);
            $table->string('c6_projet', 9)->default('      ');
            $table->string('c6_itproj', 9)->default('  ');
            $table->double('c6_potenci')->default(0.0);
            $table->string('c6_licita', 9)->default('      ');
            $table->string('c6_regwms', 9)->default(' ');
            $table->binary('c6_mopc')->nullable();
            $table->string('c6_numcp', 92)->default('            ');
            $table->string('c6_numsc', 9)->default('      ');
            $table->string('c6_itemsc', 9)->default('    ');
            $table->string('c6_sugentr', 9)->default('        ');
            $table->string('c6_itemed', 9)->default('   ');
            $table->double('c6_funrura')->default(0.0);
            $table->double('c6_fetab')->default(0.0);
            $table->string('c6_codrom', 90)->default('          ');
            $table->string('c6_program', 90)->default('          ');
            $table->string('c6_turno', 9)->default(' ');
            $table->string('c6_pedcom', 9)->default('      ');
            $table->string('c6_itpc', 9)->default('    ');
            $table->string('c6_filped', 9)->default('      ');
            $table->string('c6_codlan', 9)->default('      ');
            $table->string('c6_gcplt', 9)->default('        ');
            $table->string('c6_gcpit', 9)->default('      ');
            $table->string('c6_categ', 9)->default('  ');
            $table->string('c6_ctvar', 90)->default('          ');
            $table->string('c6_solcom', 9)->default('      ');
            $table->string('c6_vdmost', 9)->default(' ');
            $table->binary('c6_vdobs')->nullable();
            $table->string('c6_pvcomop', 9)->default(' ');
            $table->string('c6_itemgar', 9)->default('  ');
            $table->string('c6_orcgar', 9)->default('      ');
            $table->string('c6_horent', 9)->default('    ');
            $table->string('c6_almterc', 9)->default('  ');
            $table->string('c6_itempc', 9)->default('      ');
            $table->string('c6_numpcom', 95)->default('               ');
            $table->string('c6_pene', 9)->default('    ');
            $table->string('c6_ccusto', 9)->default('         ');
            $table->string('c6_cc', 9)->default('         ');
            $table->string('c6_pmsid', 90)->default('          ');
            $table->string('c6_rateio', 9)->default(' ');
            $table->string('c6_d1serie', 9)->default('   ');
            $table->string('c6_sdoc', 9)->default('   ');
            $table->string('c6_sdocded', 9)->default('   ');
            $table->string('c6_sdocsd1', 9)->default('   ');
            $table->string('c6_sdocori', 9)->default('   ');
            $table->string('c6_cultra', 90)->default('          ');
            $table->string('c6_codinf', 9)->default('      ');
            $table->string('c6_codlpre', 9)->default('      ');
            $table->string('c6_d1item', 9)->default('    ');
            $table->string('c6_itlpre', 9)->default('   ');
            $table->string('c6_d1doc', 95)->default('               ');
            $table->string('c6_revprod', 9)->default('   ');
            $table->string('c6_prodfin', 95)->default('               ');
            $table->string('c6_pedvinc', 9)->default('      ');
            $table->string('c6_tpprod', 9)->default(' ');
            $table->string('c6_provent', 9)->default('  ');
            $table->string('d_e_l_e_t_', 9)->default(' ');
            $table->bigInteger('r_e_c_n_o_')->default(0);
            $table->bigInteger('r_e_c_d_e_l_')->default(0);
            $table->double('c6_xconv')->default(0.0);
            $table->string('c6_xcenvli', 90)->default('          ');
            $table->string('c6_xcodcli', 95)->default('               ');
            $table->string('c6_xoccli', 90)->default('                    ');
            $table->string('c6_oper', 9)->default('  ');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sc6010');
    }
};
