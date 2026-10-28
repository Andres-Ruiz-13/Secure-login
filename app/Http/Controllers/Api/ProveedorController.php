<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Services\AuditService;
use App\Services\DebugLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Listado paginado de proveedores (Lectura todos los roles).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Proveedor::query();

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', $term)
                  ->orWhere('nit', 'like', $term)
                  ->orWhere('correo', 'like', $term);
            });
        }

        $perPage = min((int) ($request->get('per_page', 10)), 100);
        $proveedores = $query->orderBy('nombre')->paginate($perPage);

        return response()->json([
            'data' => $proveedores->items(),
            'meta' => [
                'current_page' => $proveedores->currentPage(),
                'last_page'    => $proveedores->lastPage(),
                'per_page'     => $proveedores->perPage(),
                'total'        => $proveedores->total(),
            ],
        ]);
    }

    /**
     * Muestra un proveedor con sus facturas y productos.
     */
    public function show(Proveedor $proveedor): JsonResponse
    {
        return response()->json([
            'proveedor' => $proveedor->load(['productos', 'compras']),
        ]);
    }

    /**
     * Registrar proveedor (Admin y Bodeguero; Vendedor -> 403).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nit'       => ['required', 'string', 'max:50', 'unique:proveedores,nit'],
            'nombre'    => ['required', 'string', 'max:150'],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'correo'    => ['nullable', 'email', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $proveedor = Proveedor::create([
            'nit'       => $validated['nit'],
            'nombre'    => $validated['nombre'],
            'telefono'  => $validated['telefono'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'correo'    => $validated['correo'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        DebugLogService::log('PROVEEDOR_CREADO', ['id' => $proveedor->id, 'nit' => $proveedor->nit, 'nombre' => $proveedor->nombre]);
        AuditService::log('proveedor_creado', Proveedor::class, $proveedor->id, ['nit' => $proveedor->nit]);

        return response()->json([
            'message'   => 'Proveedor registrado exitosamente.',
            'proveedor' => $proveedor,
        ], 201);
    }

    /**
     * Actualizar proveedor (Admin y Bodeguero; Vendedor -> 403).
     */
    public function update(Request $request, Proveedor $proveedor): JsonResponse
    {
        $validated = $request->validate([
            'nit'       => ['nullable', 'string', 'max:50', 'unique:proveedores,nit,' . $proveedor->id],
            'nombre'    => ['nullable', 'string', 'max:150'],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'correo'    => ['nullable', 'email', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $proveedor->update($validated);

        DebugLogService::log('PROVEEDOR_ACTUALIZADO', ['id' => $proveedor->id, 'nombre' => $proveedor->nombre]);

        return response()->json([
            'message'   => 'Proveedor actualizado correctamente.',
            'proveedor' => $proveedor,
        ]);
    }

    /**
     * Eliminar proveedor (Admin y Bodeguero; Vendedor -> 403).
     */
    public function destroy(Proveedor $proveedor): JsonResponse
    {
        $id = $proveedor->id;
        $nombre = $proveedor->nombre;
        $proveedor->delete();

        DebugLogService::log('PROVEEDOR_ELIMINADO', ['id' => $id, 'nombre' => $nombre]);

        return response()->json([
            'message' => "Proveedor '{$nombre}' eliminado correctamente.",
        ]);
    }
}

