<?php

namespace App\Services;

use App\Models\ConfiguracionOcr;
use App\Models\EjecucionOcr;
use App\Models\DocumentoOcr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DespachadorOcr
{
    /**
     * Mapa de parser IDs conocidos. Se usa para inyectar el parser_id
     * que ocr_cli.py necesita para elegir el regex de extracción de texto.
     * Si la empresa no está acá, ocr_cli.py usará extract_tables() (fallback).
     */
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

    /**
     * Procesa un documento corriendo ocr_cli.py y guardando el resultado.
     * Sync (sin cola). Para cola, envolver en un Job.
     *
     * @throws \RuntimeException
     */
    public function procesar(DocumentoOcr $document): DocumentoOcr
    {
        $pdfPath = $document->pdfAbsolutePath();

        if (!is_file($pdfPath)) {
            throw new \RuntimeException("PDF no encontrado en: {$pdfPath}");
        }

        $configs = $this->configuracionesParaCli();

        if (empty($configs)) {
            throw new \RuntimeException(
                'No hay configuraciones OCR activas. Configurá al menos una en Configuración OCR.'
            );
        }

        $previous = [
            'status' => $document->status,
            'extracted' => $document->extracted,
        ];

        $document->update([
            'status' => DocumentoOcr::STATUS_PROCESSING,
            'error' => null,
        ]);

        try {
            $result = $this->ejecutarCli($pdfPath, $configs);

            $document->update([
                'status' => DocumentoOcr::STATUS_REVIEW,
                'extracted' => $result,
                'error' => null,
            ]);

            $this->registrarEjecucion(
                $document,
                DocumentoOcr::STATUS_REVIEW,
                $previous,
                null
            );

            return $document->fresh();
        } catch (\Throwable $e) {
            $document->update([
                'status' => DocumentoOcr::STATUS_FAILED,
                'error' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            $this->registrarEjecucion(
                $document,
                DocumentoOcr::STATUS_FAILED,
                $previous,
                $e->getMessage()
            );

            Log::error('OCR failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Transforma las configuraciones planas (DB) al formato que ocr_cli.py espera.
     */
    private function configuracionesParaCli(): array
    {
        return ConfiguracionOcr::query()
            ->where('active', true)
            ->orderByDesc('priority')
            ->get()
            ->map(fn(ConfiguracionOcr $c) => $this->formatearParaCli($c))
            ->all();
    }

    private function formatearParaCli(ConfiguracionOcr $c): array
    {
        $parserId = $this->detectarParserId($c->nombre ?? '');

        return [
            // Identidad de la config (como la espera ocr_cli.py)
            'name' => $c->name,
            'active' => true,
            'priority' => (int) $c->priority,

            // Parser ID: crítico para el regex de texto
            'codigo' => $parserId ?: (string) $c->codigo,

            // Código TOTVS (a1_cod) — se usa para SC5 y para `cliente_codigo`
            'cliente_codigo' => (string) $c->codigo,
            'empresa_codigo' => (string) $c->codigo,
            'cliente_codigo_confirmado' => true,

            // Metadatos comerciales
            'nombre' => $c->nombre,
            'nombre_fantasia' => $c->nombre_fantasia,
            'cuit_cuil' => $c->cuit_cuil,
            'tienda' => (string) $c->tienda,
            'filial' => (string) $c->filial,
            'vendedor' => (string) $c->vendedor,

            // Reglas de OCR
            'formato_numerico' => $c->formato_numerico,
            'identificadores_texto' => $c->identificadores_texto ?? [],
            'identificadores_ocr' => $c->identificadores_ocr ?? [],
            'email_origen' => $c->email_origen ?? [],

            // Columnas ANIDADAS (así lo espera ocr_cli.py)
            'columnas' => [
                'codigo' => $c->columnas_codigo ?? [],
                'cantidad' => $c->columnas_cantidad ?? [],
            ],
        ];
    }

    private function detectarParserId(string $nombre): string
    {
        $upper = mb_strtoupper($nombre);

        foreach (self::PARSERS_CONOCIDOS as $needle => $parserId) {
            if (str_contains($upper, $needle)) {
                return $parserId;
            }
        }

        return '';
    }

    private function ejecutarCli(string $pdfPath, array $configs): array
    {
        $python = config('ocr.python_bin');
        $script = config('ocr.cli_path');
        $timeout = (int) config('ocr.timeout', 120);

        if (!is_file($python)) {
            throw new \RuntimeException("Python no encontrado: {$python}");
        }

        if (!is_file($script)) {
            throw new \RuntimeException("ocr_cli.py no encontrado: {$script}");
        }

        $process = new Process([$python, $script, $pdfPath]);
        $process->setTimeout($timeout);
        $process->setInput(json_encode($configs, JSON_UNESCAPED_UNICODE));

        $process->setEnv([
            'TESSERACT_BINARY' => config('ocr.tesseract_bin'),
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            $stderr = trim($process->getErrorOutput());
            $stdout = trim($process->getOutput());
            throw new \RuntimeException(
                'OCR falló: ' . ($stderr ?: $stdout ?: 'Sin salida')
            );
        }

        $output = trim($process->getOutput());
        $data = json_decode($output, true);

        if (!is_array($data)) {
            throw new \RuntimeException(
                'OCR devolvió JSON inválido: ' . mb_substr($output, 0, 500)
            );
        }

        if (empty($data['articulos']) || !is_array($data['articulos'])) {
            $diag = $data['diagnostico'] ?? [];
            throw new \RuntimeException(
                'OCR no extrajo artículos. Diagnóstico: '
                . json_encode($diag, JSON_UNESCAPED_UNICODE)
                . ' | Raw: ' . mb_substr($output, 0, 500)
            );
        }

        return $data;
    }

    private function registrarEjecucion(
        DocumentoOcr $document,
        string $status,
        array $previous,
        ?string $error
    ): void {
        EjecucionOcr::create([
            'document_id' => $document->id,
            'status' => $status,
            'previous_status' => $previous['status'] ?? null,
            'previous_extracted' => $previous['extracted'] ?? null,
            'config_hash' => $this->hashConfiguraciones(),
            'error' => $error,
        ]);
    }

    private function hashConfiguraciones(): string
    {
        $activas = ConfiguracionOcr::query()
            ->where('active', true)
            ->orderBy('id')
            ->get(['id', 'version'])
            ->map(fn($c) => "{$c->id}:{$c->version}")
            ->implode('|');

        return hash('sha256', $activas);
    }
}