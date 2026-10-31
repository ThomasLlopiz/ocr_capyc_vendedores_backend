<?php

namespace App\Console\Commands;

use App\Models\ConfiguracionOcr;
use App\Models\DocumentoOcr;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class OcrDebug extends Command
{
    protected $signature = 'ocr:debug {id : ID del DocumentoOcr}';
    protected $description = 'Ejecuta ocr_cli.py sobre un documento y muestra stdout/stderr';

    private const PARSERS_CONOCIDOS = [
        'MANFREY' => '000222',
        'TREMBLAY' => 'TREMBLAY',
        'DON EMILIO' => 'DONEMILIO',
        'PROLAIT' => 'PROLAIT',
        'FAMILIA BEAUDROIT' => 'PROLAIT',
        'FULLANISIMA' => 'FULLANISIMA',
        'ELCOR' => '000265',
        'RAMOLAC' => '000215',
        'TREGAR' => '000050',
        'GARCIA HNOS' => '000050',
        'MOLFINO' => '000345',
        'LA PAULINA' => '000345',
        'INVERSIONES PARA EL AGRO' => '000382',
    ];

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $doc = DocumentoOcr::find($id);

        if (!$doc) {
            $this->error("Documento {$id} no encontrado");
            return 1;
        }

        $pdfPath = $doc->pdfAbsolutePath();

        $this->line("=== PDF ===");
        $this->line($pdfPath);
        if (!is_file($pdfPath)) {
            $this->error("El PDF no existe en esa ruta");
            return 1;
        }

        $configs = ConfiguracionOcr::where('active', true)
            ->orderByDesc('priority')
            ->get()
            ->map(fn($c) => $this->formatearParaCli($c))
            ->values()
            ->all();

        $this->line("");
        $this->line("=== CONFIGS ACTIVAS ===");
        $this->line("Cantidad: " . count($configs));
        foreach ($configs as $c) {
            $this->line("  - name={$c['name']} | codigo_parser={$c['codigo']} | formato={$c['formato_numerico']}");
        }

        $jsonInput = json_encode($configs, JSON_UNESCAPED_UNICODE);

        $this->line("");
        $this->line("=== EJECUTANDO ocr_cli.py ===");

        $python = config('ocr.python_bin');
        $script = config('ocr.cli_path');

        $process = new Process([$python, $script, $pdfPath]);
        $process->setTimeout((int) config('ocr.timeout', 120));
        $process->setInput($jsonInput);
        $process->setEnv(['TESSERACT_BINARY' => config('ocr.tesseract_bin')]);
        $process->run();

        $this->line("");
        $this->line("=== EXIT CODE ===");
        $this->line((string) $process->getExitCode());

        $this->line("");
        $this->line("=== STDERR ===");
        $this->line($process->getErrorOutput() ?: '(vacío)');

        $this->line("");
        $this->line("=== STDOUT (primeros 3000 chars) ===");
        $this->line(mb_substr($process->getOutput(), 0, 3000));

        $stdout = trim($process->getOutput());
        $decoded = json_decode($stdout, true);

        if (is_array($decoded) && !empty($decoded['articulos'])) {
            $this->line("");
            $this->info("=== OCR OK: " . count($decoded['articulos']) . " artículos ===");
            $this->line(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return 0;
        }

        $this->line("");
        $this->error("=== OCR FALLÓ o no extrajo artículos ===");
        return 1;
    }

    private function formatearParaCli(ConfiguracionOcr $c): array
    {
        $parserId = $this->detectarParserId($c->nombre ?? '');

        return [
            'name' => $c->name,
            'active' => true,
            'priority' => (int) $c->priority,
            'codigo' => $parserId ?: (string) $c->codigo,
            'cliente_codigo' => (string) $c->codigo,
            'empresa_codigo' => (string) $c->codigo,
            'cliente_codigo_confirmado' => true,
            'nombre' => $c->nombre,
            'nombre_fantasia' => $c->nombre_fantasia,
            'cuit_cuil' => $c->cuit_cuil,
            'tienda' => (string) $c->tienda,
            'filial' => (string) $c->filial,
            'vendedor' => (string) $c->vendedor,
            'formato_numerico' => $c->formato_numerico,
            'identificadores_texto' => $c->identificadores_texto ?? [],
            'identificadores_ocr' => $c->identificadores_ocr ?? [],
            'email_origen' => $c->email_origen ?? [],
            'columnas' => [
                'codigo' => $c->columnas_codigo ?? [],
                'cantidad' => $c->columnas_cantidad ?? [],
            ],
        ];
    }

    private function detectarParserId(string $nombre): string
    {
        $upper = mb_strtoupper($nombre);
        foreach (self::PARSERS_CONOCIDOS as $needle => $id) {
            if (str_contains($upper, $needle)) {
                return $id;
            }
        }
        return '';
    }
}