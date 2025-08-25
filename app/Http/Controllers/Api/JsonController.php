<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class JsonController extends Controller
{
    // Carpeta donde están los JSON procesados
    private $jsonPath = 'pdfs_procesados';

    /**
     * Actualiza el contenido de un JSON
     */
    public function update(Request $request, $nombre)
    {
        try {
            // Construimos la ruta absoluta al archivo
            $ruta = base_path($this->jsonPath . '/' . $nombre . '.json');

            // Verificamos que el archivo exista
            if (! File::exists($ruta)) {
                return response()->json([
                    'success' => false,
                    'message' => "El archivo {$nombre}.json no existe",
                ], 404);
            }

            // Obtenemos los datos enviados desde el frontend
            $datos = $request->all();

            // Guardamos el contenido actualizado en formato JSON
            File::put($ruta, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return response()->json([
                'success' => true,
                'message' => "Archivo {$nombre}.json actualizado correctamente",
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error al actualizar el JSON: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el contenido de un JSON
     */
    public function show($nombre)
    {
        try {
            // Construimos la ruta absoluta al archivo
            $ruta = base_path($this->jsonPath . '/' . $nombre . '.json');

            // Verificamos que el archivo exista
            if (! File::exists($ruta)) {
                return response()->json([
                    'success' => false,
                    'message' => "El archivo {$nombre}.json no existe",
                ], 404);
            }

            // Leemos el contenido y lo devolvemos
            $contenido = json_decode(File::get($ruta), true);

            return response()->json([
                'success' => true,
                'data'    => $contenido,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error al leer el JSON: " . $e->getMessage(),
            ], 500);
        }
    }
}
