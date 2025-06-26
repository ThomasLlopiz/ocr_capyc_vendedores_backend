<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index(): JsonResponse
    {
        $empresas = Empresa::all()->map(function ($empresa) {
            $empresa->codigo   = str_pad($empresa->codigo, 6, '0', STR_PAD_LEFT);
            $empresa->vendedor = str_pad($empresa->vendedor, 6, '0', STR_PAD_LEFT);
            return $empresa;
        });
        return response()->json($empresas);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'codigo'     => 'required|string|size:6',
            'tienda'     => 'required|numeric|between:1,9999',
            'nombre'     => 'required|string|max:200',
            'n_fantasia' => 'required|string|max:200',
            'cuit_cuil'  => 'required|numeric|digits:11',
            'vendedor'   => 'required|string|size:6',
        ]);

        $empresa = Empresa::create([
            'codigo'     => str_pad($validatedData['codigo'], 6, '0', STR_PAD_LEFT),
            'tienda'     => $validatedData['tienda'],
            'nombre'     => $validatedData['nombre'],
            'n_fantasia' => $validatedData['n_fantasia'],
            'cuit_cuil'  => $validatedData['cuit_cuil'],
            'vendedor'   => str_pad($validatedData['vendedor'], 6, '0', STR_PAD_LEFT),
        ]);

        return response()->json(['message' => 'Empresa creada exitosamente', 'data' => $empresa], 201);
    }

    public function show($id): JsonResponse
    {
        $empresa = Empresa::findOrFail($id);
        return response()->json($empresa);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validatedData = $request->validate([
            'codigo'     => 'required|string|size:6',
            'tienda'     => 'required|numeric|between:1,9999',
            'nombre'     => 'required|string|max:200',
            'n_fantasia' => 'required|string|max:200',
            'cuit_cuil'  => 'required|numeric|digits:11',
            'vendedor'   => 'required|string|size:6',
        ]);

        $empresa = Empresa::findOrFail($id);
        $empresa->update([
            'codigo'     => str_pad($validatedData['codigo'], 6, '0', STR_PAD_LEFT),
            'tienda'     => $validatedData['tienda'],
            'nombre'     => $validatedData['nombre'],
            'n_fantasia' => $validatedData['n_fantasia'],
            'cuit_cuil'  => $validatedData['cuit_cuil'],
            'vendedor'   => str_pad($validatedData['vendedor'], 6, '0', STR_PAD_LEFT),
        ]);

        return response()->json(['message' => 'Empresa actualizada exitosamente', 'data' => $empresa]);
    }

    public function destroy($id): JsonResponse
    {
        $empresa = Empresa::findOrFail($id);
        $empresa->delete();
        return response()->json(['message' => 'Empresa eliminada exitosamente']);
    }
}
