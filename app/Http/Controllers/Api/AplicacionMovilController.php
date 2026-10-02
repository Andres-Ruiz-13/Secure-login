<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use App\Services\ImageHashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AplicacionMovilController extends Controller
{
    /**
     * Consulta de producto por código de barras.
     * GET /api/aplicacion_movil/producto/codigo/{codigo}
     */
    public function porCodigoBarras(string $codigo): JsonResponse
    {
        $producto = Producto::with(['categoria', 'proveedor'])
            ->where('codigo_barras', trim($codigo))
            ->where('is_active', true)
            ->first();

        if (! $producto) {
            return response()->json([
                'encontrado' => false,
                'message'    => "No se encontró ningún producto con el código de barras '{$codigo}'.",
            ], 404);
        }

        return response()->json([
            'encontrado' => true,
            'producto'   => new ProductoResource($producto),
        ]);
    }

    /**
     * Consulta de productos por nombre.
     * GET /api/aplicacion_movil/producto/nombre?q=martillo
     */
    public function porNombre(Request $request): JsonResponse
    {
        $term = trim($request->get('q', ''));

        if (strlen($term) < 2) {
            return response()->json([
                'message' => 'El término de búsqueda debe tener al menos 2 caracteres.',
                'data'    => [],
            ], 422);
        }

        $productos = Producto::with(['categoria', 'proveedor'])
            ->where('is_active', true)
            ->where('nombre', 'like', "%{$term}%")
            ->take(20)
            ->get();

        return response()->json([
            'total' => $productos->count(),
            'data'  => ProductoResource::collection($productos),
        ]);
    }

    /**
     * Reconocimiento de producto por imagen mediante Average Hash (aHash) y distancia de Hamming.
     * POST /api/aplicacion_movil/producto/imagen
     */
    public function porImagen(Request $request): JsonResponse
    {
        $request->validate([
            'imagen' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('imagen');
        $realPath = $file->getRealPath();

        $queryHash = ImageHashService::generateHash($realPath);

        if (! $queryHash) {
            return response()->json([
                'encontrado' => false,
                'message'    => 'No fue posible procesar la imagen enviada. Verifique el formato.',
            ], 422);
        }

        $maxDistance = (int) config('inbot.max_hamming_distance', 10);

        // Consultar productos con hash precalculado y comparar distancia de Hamming
        $candidatos = Producto::with(['categoria', 'proveedor'])
            ->whereNotNull('image_hash')
            ->where('is_active', true)
            ->get(['id', 'codigo_barras', 'nombre', 'categoria_id', 'proveedor_id', 'imagen', 'image_hash', 'stock_actual', 'stock_minimo', 'precio_venta', 'is_active']);

        $mejorCoincidencia = null;
        $menorDistancia = 65;

        foreach ($candidatos as $producto) {
            $distancia = ImageHashService::hammingDistance($queryHash, $producto->image_hash);

            if ($distancia < $menorDistancia && $distancia <= $maxDistance) {
                $menorDistancia = $distancia;
                $mejorCoincidencia = $producto;
            }
        }

        if (! $mejorCoincidencia) {
            return response()->json([
                'encontrado'     => false,
                'hash_generado'  => $queryHash,
                'menor_distancia'=> $menorDistancia < 65 ? $menorDistancia : null,
                'message'        => 'No se reconoció ningún producto coincidente en el catálogo.',
            ], 404);
        }

        // Confianza del 0 al 100% (64 bits totales: 0 dist = 100%, 10 dist = ~84%)
        $confianza = round(((64 - $menorDistancia) / 64) * 100, 1);

        return response()->json([
            'encontrado'           => true,
            'distancia_hamming'    => $menorDistancia,
            'porcentaje_confianza' => $confianza,
            'producto'             => new ProductoResource($mejorCoincidencia),
        ]);
    }

    /**
     * Chatbot asistente de inventario y consultas en tiempo real.
     * POST /api/aplicacion_movil/chatbot
     */
    public function chatbot(Request $request): JsonResponse
    {
        $request->validate([
            'mensaje' => ['required', 'string', 'max:500'],
        ]);

        $pregunta = mb_strtolower(trim($request->mensaje), 'UTF-8');

        // 1. Consulta general de productos con stock bajo o alertas
        if (str_contains($pregunta, 'stock bajo') || str_contains($pregunta, 'agotando') || str_contains($pregunta, 'crítico')) {
            $bajos = Producto::stockBajo()->where('is_active', true)->take(5)->get();

            if ($bajos->isEmpty()) {
                return response()->json([
                    'respuesta' => '¡Buenas noticias! Actualmente no hay productos con stock por debajo del mínimo.',
                    'tipo'      => 'alerta_stock',
                    'productos' => [],
                ]);
            }

            $lista = $bajos->map(fn ($p) => "• {$p->nombre}: {$p->stock_actual} unidades (mínimo: {$p->stock_minimo})")->implode("\n");

            return response()->json([
                'respuesta' => "Atención, se detectaron productos con stock bajo:\n{$lista}",
                'tipo'      => 'alerta_stock',
                'productos' => ProductoResource::collection($bajos),
            ]);
        }

        // 2. Consulta de total de inventario
        if (str_contains($pregunta, 'cuántos productos') || str_contains($pregunta, 'total productos') || str_contains($pregunta, 'resumen')) {
            $totalReferencias = Producto::where('is_active', true)->count();
            $totalStock = (int) Producto::where('is_active', true)->sum('stock_actual');

            return response()->json([
                'respuesta' => "Actualmente el sistema cuenta con {$totalReferencias} referencias activas y un total de {$totalStock} unidades en almacén.",
                'tipo'      => 'resumen_inventario',
            ]);
        }

        // 3. Consulta de un producto por nombre o palabra clave
        // Extraer palabras significativas
        $palabras = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $pregunta)), function ($palabra) {
            $stopWords = ['hola', 'hay', 'cuanto', 'cuánto', 'cuesta', 'precio', 'stock', 'existencias', 'de', 'el', 'la', 'los', 'las', 'un', 'una', 'para', 'tienen', 'queda', 'quedan', 'por', 'favor'];
            return mb_strlen($palabra) >= 3 && ! in_array($palabra, $stopWords, true);
        });

        if (! empty($palabras)) {
            $query = Producto::where('is_active', true);
            foreach ($palabras as $p) {
                $query->where('nombre', 'like', "%{$p}%");
            }

            $encontrados = $query->take(3)->get();

            if ($encontrados->isNotEmpty()) {
                $detalles = $encontrados->map(function ($prod) {
                    $estadoStock = $prod->stock_actual > $prod->stock_minimo
                        ? "disponible ({$prod->stock_actual} uds)"
                        : "¡stock bajo! ({$prod->stock_actual} uds)";

                    return "• {$prod->nombre}: Precio $" . number_format((float) $prod->precio_venta, 2) . " | Stock: {$estadoStock} | Cód: {$prod->codigo_barras}";
                })->implode("\n");

                return response()->json([
                    'respuesta' => "He encontrado la siguiente información en el inventario:\n{$detalles}",
                    'tipo'      => 'consulta_producto',
                    'productos' => ProductoResource::collection($encontrados),
                ]);
            }
        }

        // Respuesta por defecto con guía de uso
        return response()->json([
            'respuesta' => 'Hola, soy INBOT Asistente. Puedo ayudarte a consultar existencias, precios y alertas de stock. Prueba preguntarme por ejemplo: "¿Cuánto cuesta el martillo?", "¿Hay existencias de pintura?" o "¿Qué productos tienen stock bajo?".',
            'tipo'      => 'ayuda',
        ]);
    }
}
