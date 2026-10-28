<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditService;
use App\Services\DebugLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriaController extends Controller
{
    /**
     * Listado de categorías (Lectura pública / todos los roles).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::withCount('productos');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where('name', 'like', $term)
                  ->orWhere('description', 'like', $term);
        }

        $perPage = min((int) ($request->get('per_page', 10)), 100);
        $categorias = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $categorias->items(),
            'meta' => [
                'current_page' => $categorias->currentPage(),
                'last_page'    => $categorias->lastPage(),
                'per_page'     => $categorias->perPage(),
                'total'        => $categorias->total(),
            ],
        ]);
    }

    /**
     * Muestra una categoría específica con sus productos.
     */
    public function show(Category $categoria): JsonResponse
    {
        return response()->json([
            'categoria' => $categoria->load(['productos']),
        ]);
    }

    /**
     * Crear categoría (Admin y Bodeguero; Vendedor -> 403).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'      => ['required_without:name', 'string', 'max:100'],
            'name'        => ['required_without:nombre', 'string', 'max:100'],
            'slug'        => ['nullable', 'string', 'max:120', 'unique:categories,slug'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $name = $validated['nombre'] ?? $validated['name'];
        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($name);

        // Asegurar unicidad de slug
        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $categoria = Category::create([
            'name'        => $name,
            'slug'        => $slug,
            'description' => $validated['descripcion'] ?? $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        DebugLogService::log('CATEGORIA_CREADA', ['id' => $categoria->id, 'nombre' => $categoria->name]);
        AuditService::log('categoria_creada', Category::class, $categoria->id, ['name' => $categoria->name]);

        return response()->json([
            'message'   => 'Categoría creada exitosamente.',
            'categoria' => $categoria,
        ], 201);
    }

    /**
     * Actualizar categoría (Admin y Bodeguero; Vendedor -> 403).
     */
    public function update(Request $request, Category $categoria): JsonResponse
    {
        $validated = $request->validate([
            'nombre'      => ['nullable', 'string', 'max:100'],
            'name'        => ['nullable', 'string', 'max:100'],
            'slug'        => ['nullable', 'string', 'max:120', 'unique:categories,slug,' . $categoria->id],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['nombre']) || ! empty($validated['name'])) {
            $categoria->name = $validated['nombre'] ?? $validated['name'];
        }

        if (isset($validated['slug'])) {
            $categoria->slug = Str::slug($validated['slug']);
        }

        if (array_key_exists('descripcion', $validated) || array_key_exists('description', $validated)) {
            $categoria->description = $validated['descripcion'] ?? $validated['description'] ?? null;
        }

        if (isset($validated['is_active'])) {
            $categoria->is_active = (bool) $validated['is_active'];
        }

        $categoria->save();

        DebugLogService::log('CATEGORIA_ACTUALIZADA', ['id' => $categoria->id, 'nombre' => $categoria->name]);

        return response()->json([
            'message'   => 'Categoría actualizada correctamente.',
            'categoria' => $categoria,
        ]);
    }

    /**
     * Eliminar categoría (Admin y Bodeguero; Vendedor -> 403).
     */
    public function destroy(Category $categoria): JsonResponse
    {
        $id = $categoria->id;
        $name = $categoria->name;
        $categoria->delete();

        DebugLogService::log('CATEGORIA_ELIMINADA', ['id' => $id, 'nombre' => $name]);

        return response()->json([
            'message' => "Categoría '{$name}' eliminada correctamente.",
        ]);
    }
}

