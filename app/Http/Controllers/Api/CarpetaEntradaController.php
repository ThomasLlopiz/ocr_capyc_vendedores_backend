<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentoOcr;
use App\Services\DespachadorOcr;
use Illuminate\Http\Request;

class CarpetaEntradaController extends Controller
{
    /**
     * GET /api/input-pdfs
     * Lista los PDFs de la carpeta pdfs/ con su estado en documentos_ocr.
     */
    public function index(Request $request)
    {
        abort_unless(
            in_array($request->user()->role ?? 'user', ['admin', 'operador'], true),
            403,
            'Solo operadores y admin'
        );

        $pdfsDir = base_path(config('ocr.pdfs_dir'));

        if (!is_dir($pdfsDir)) {
            return response()->json(['data' => []]);
        }

        $files = [];
        foreach (scandir($pdfsDir) as $file) {
            if (!str_ends_with(strtolower($file), '.pdf')) {
                continue;
            }

            $path = $pdfsDir . DIRECTORY_SEPARATOR . $file;
            if (!is_file($path)) {
                continue;
            }

            $sha256 = hash_file('sha256', $path);
            $doc = DocumentoOcr::where('sha256', $sha256)->first();

            $files[] = [
                'id' => $file,
                'name' => $file,
                'status' => $doc?->status ?? 'pending',
                'document_id' => $doc?->id,
                'size' => filesize($path),
                'modified_at' => date('c', filemtime($path)),
            ];
        }

        usort($files, fn($a, $b) => strcmp($a['name'], $b['name']));

        return response()->json(['data' => $files]);
    }

    /**
     * POST /api/input-pdfs/process
     * Procesa un lote de PDFs (sync, usa DespachadorOcr).
     */
    public function import(Request $request, DespachadorOcr $despachador)
    {
        abort_unless(
            in_array($request->user()->role ?? 'user', ['admin', 'operador'], true),
            403,
            'Solo operadores y admin'
        );

        $data = $request->validate([
            'ids' => 'required|array|min:1|max:20',
            'ids.*' => 'required|string',
        ]);

        $pdfsDir = base_path(config('ocr.pdfs_dir'));

        $imported = [];
        $skipped = [];
        $failed = [];

        foreach ($data['ids'] as $filename) {
            $filename = basename($filename);
            $path = $pdfsDir . DIRECTORY_SEPARATOR . $filename;

            if (!is_file($path)) {
                $failed[] = ['name' => $filename, 'message' => 'Archivo no encontrado'];
                continue;
            }

            $sha256 = hash_file('sha256', $path);

            $existing = DocumentoOcr::where('sha256', $sha256)->first();
            if ($existing) {
                $skipped[] = [
                    'id' => $existing->id,
                    'name' => $filename,
                    'message' => 'Ya procesado (status: ' . $existing->status . ')',
                ];
                continue;
            }

            try {
                $doc = DocumentoOcr::create([
                    'original_name' => $filename,
                    'storage_path' => config('ocr.pdfs_dir') . '/' . $filename,
                    'sha256' => $sha256,
                    'status' => 'pending',
                    'uploaded_by' => $request->user()->id,
                ]);

                $despachador->procesar($doc);

                $imported[] = [
                    'id' => $doc->id,
                    'name' => $filename,
                    'status' => $doc->status,
                    'message' => $doc->status === 'review'
                        ? 'Procesado correctamente'
                        : 'Procesado con estado ' . $doc->status,
                ];
            } catch (\Throwable $e) {
                $failed[] = [
                    'name' => $filename,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);
    }
}