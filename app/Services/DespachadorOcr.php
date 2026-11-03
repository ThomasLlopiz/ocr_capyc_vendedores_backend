<?php

namespace App\Services;

use App\Models\ConfiguracionOcr;
use App\Models\EjecucionOcr;
use App\Models\DocumentoOcr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DespachadorOcr
{
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

            $this->registrarEjecucion($document, DocumentoOcr::STATUS_REVIEW, $previous, null);

            return $document->fresh();
        } catch (\Throwable $e) {
            $document->update([
                'status' => DocumentoOcr::STATUS_FAILED,
                'error' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            $this->registrarEjecucion($document, DocumentoOcr::STATUS_FAILED, $previous, $e->getMessage());

            Log::error('OCR failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function configuracionesParaCli(): array
    {
        return ConfiguracionOcr::query()
            ->where('active', true)
            ->orderByDesc('priority')
            ->get()
            ->map(fn(ConfiguracionOcr $c) => $this->formatearParaCli($c))
            ->all();
    }

    public function formatearParaCli(ConfiguracionOcr $c): array
    {
        return [
            'name' => $c->name,
            'active' => true,
            'priority' => (int) $c->priority,
            'codigo' => (string) $c->codigo,
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
            'modo_extraccion' => $c->modo_extraccion ?: 'codigo_cantidad',
            'linea_regex' => $c->linea_regex,
            'codigo_regex' => $c->codigo_regex ?? null,
            'identificadores_texto' => $c->identificadores_texto ?? [],
            'identificadores_ocr' => $c->identificadores_ocr ?? [],
            'email_origen' => $c->email_origen ?? [],
            'columnas' => [
                'codigo' => $c->columnas_codigo ?? [],
                'cantidad' => $c->columnas_cantidad ?? [],
            ],
        ];
    }

    private function ejecutarCli(string $pdfPath, array $configs): array
    {
        // ← LEER DE CONFIG (no hardcodear)
        $python = config('ocr.python_bin');
        $script = config('ocr.cli_path');
        $timeout = (int) config('ocr.timeout', 120);

        if (!is_file($python)) {
            throw new \RuntimeException(
                "Python no encontrado en: {$python}\n" .
                "PHP corre como: " . get_current_user() . "\n" .
                "Verificá OCR_PYTHON_BIN en .env"
            );
        }

        if (!is_file($script)) {
            throw new \RuntimeException(
                "ocr_cli.py no encontrado en: {$script}\n" .
                "Verificá OCR_CLI_PATH en .env"
            );
        }

        $process = new Process([$python, $script, $pdfPath]);
        $process->setTimeout($timeout);
        $process->setInput(json_encode($configs, JSON_UNESCAPED_UNICODE));
        $process->setEnv(['TESSERACT_BINARY' => config('ocr.tesseract_bin')]);
        $process->run();

        if (!$process->isSuccessful()) {
            $stderr = trim($process->getErrorOutput());
            $stdout = trim($process->getOutput());
            throw new \RuntimeException('OCR falló: ' . ($stderr ?: $stdout ?: 'Sin salida'));
        }

        $output = trim($process->getOutput());
        $data = json_decode($output, true);

        if (!is_array($data)) {
            throw new \RuntimeException('OCR devolvió JSON inválido: ' . mb_substr($output, 0, 500));
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

    private function registrarEjecucion(DocumentoOcr $document, string $status, array $previous, ?string $error): void
    {
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