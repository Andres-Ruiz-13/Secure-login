<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportWordService
{
    /**
     * Genera un reporte en Word (.docx) usando python-docx y generate_report.py
     */
    public static function generate(string $tipo, array $params = []): string
    {
        $tipo = strtolower($tipo);
        $tempDir = storage_path('app/temp_reports');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $timestamp = date('Ymd_His') . '_' . rand(100, 999);
        $jsonPath = $tempDir . "/data_{$tipo}_{$timestamp}.json";
        $docxPath = $tempDir . "/reporte_{$tipo}_{$timestamp}.docx";

        $data = match ($tipo) {
            'inventario' => self::buildInventoryData(),
            'ventas'     => self::buildSalesData($params),
            'compras'    => self::buildPurchasesData($params),
            default      => throw new \InvalidArgumentException("Tipo de reporte '{$tipo}' no soportado."),
        };

        file_put_contents($jsonPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $scriptPath = base_path('generate_report.py');
        $command = 'python ' . escapeshellarg($scriptPath)
            . ' --tipo ' . escapeshellarg($tipo)
            . ' --json ' . escapeshellarg($jsonPath)
            . ' --output ' . escapeshellarg($docxPath);

        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);

        // Limpiar el JSON temporal
        @unlink($jsonPath);

        if ($exitCode !== 0 || ! file_exists($docxPath)) {
            $err = implode("\n", $output);
            throw new \RuntimeException("Fallo al generar reporte Word: {$err}");
        }

        return $docxPath;
    }

    protected static function buildInventoryData(): array
    {
        $resumen = Producto::select(
            DB::raw('COUNT(*) as total_referencias'),
            DB::raw('SUM(stock_actual) as total_unidades'),
            DB::raw('SUM(stock_actual * precio_compra) as valor_costo_inventario'),
            DB::raw('SUM(stock_actual * precio_venta) as valor_comercial_inventario'),
            DB::raw('SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) as productos_stock_bajo')
        )->first();

        $productos = Producto::with(['categoria', 'proveedor'])
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'codigo_barras' => $p->codigo_barras,
                'nombre'        => $p->nombre,
                'categoria'     => $p->categoria?->name ?? 'General',
                'stock_actual'  => $p->stock_actual,
                'stock_minimo'  => $p->stock_minimo,
                'precio_compra' => (float) $p->precio_compra,
                'precio_venta'  => (float) $p->precio_venta,
            ])
            ->toArray();

        return [
            'resumen' => [
                'total_referencias'           => (int) ($resumen->total_referencias ?? 0),
                'total_unidades'              => (int) ($resumen->total_unidades ?? 0),
                'valor_costo_inventario'      => (float) ($resumen->valor_costo_inventario ?? 0),
                'valor_comercial_inventario'  => (float) ($resumen->valor_comercial_inventario ?? 0),
                'productos_alerta_stock_bajo' => (int) ($resumen->productos_stock_bajo ?? 0),
            ],
            'productos' => $productos,
        ];
    }

    protected static function buildSalesData(array $params): array
    {
        $fecha = $params['fecha'] ?? date('Y-m-d');
        $query = Venta::with(['usuario', 'detalles.producto']);

        if (! empty($params['fecha'])) {
            $query->whereDate('fecha', $params['fecha']);
        } elseif (! empty($params['desde']) && ! empty($params['hasta'])) {
            $query->whereBetween('fecha', [$params['desde'] . ' 00:00:00', $params['hasta'] . ' 23:59:59']);
        }

        $ventas = $query->orderBy('fecha', 'desc')->get()->map(fn ($v) => [
            'numero_factura' => $v->numero_factura,
            'fecha'          => $v->fecha?->format('Y-m-d H:i') ?? $v->created_at->format('Y-m-d H:i'),
            'cliente_nombre' => $v->cliente_nombre ?? 'Consumidor Final',
            'metodo_pago'    => $v->metodo_pago,
            'vendedor'       => $v->usuario?->name ?? 'Vendedor',
            'total'          => (float) $v->total,
        ])->toArray();

        return [
            'fecha'  => $fecha,
            'ventas' => $ventas,
        ];
    }

    protected static function buildPurchasesData(array $params): array
    {
        $mes = str_pad((string) ($params['mes'] ?? date('m')), 2, '0', STR_PAD_LEFT);
        $anio = (string) ($params['anio'] ?? date('Y'));

        $query = Compra::with(['proveedor', 'usuario', 'detalles.producto'])
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes);

        $compras = $query->orderBy('fecha', 'desc')->get()->map(fn ($c) => [
            'numero_factura' => $c->numero_factura,
            'fecha'          => $c->fecha?->format('Y-m-d H:i') ?? $c->created_at->format('Y-m-d H:i'),
            'proveedor'      => $c->proveedor?->nombre ?? 'N/A',
            'nit'            => $c->proveedor?->nit ?? 'N/A',
            'usuario'        => $c->usuario?->name ?? 'Bodeguero',
            'total'          => (float) $c->total,
        ])->toArray();

        return [
            'mes'     => $mes,
            'anio'    => $anio,
            'compras' => $compras,
        ];
    }
}

