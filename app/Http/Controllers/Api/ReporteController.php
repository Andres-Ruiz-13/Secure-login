<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    /**
     * Reporte diario de ventas con agregaciones en SQL.
     */
    public function ventasDiarias(Request $request): JsonResponse
    {
        $dias = (int) ($request->get('dias', 30));

        // DB agnostic date truncation
        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', fecha)",
            'pgsql'  => "TO_CHAR(fecha, 'YYYY-MM-DD')",
            default  => "DATE(fecha)",
        };

        $reporte = Venta::select(
            DB::raw("{$dateExpr} as dia"),
            DB::raw('COUNT(*) as total_transacciones'),
            DB::raw('SUM(total) as total_ventas'),
            DB::raw('AVG(total) as ticket_promedio')
        )
            ->groupBy('dia')
            ->orderBy('dia', 'desc')
            ->limit($dias)
            ->get();

        return response()->json([
            'reporte' => $reporte,
            'dias_consultados' => $dias,
        ]);
    }

    /**
     * Reporte mensual de ventas con agregaciones en SQL.
     */
    public function ventasMensuales(Request $request): JsonResponse
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', fecha)",
            'pgsql'  => "TO_CHAR(fecha, 'YYYY-MM')",
            default  => "DATE_FORMAT(fecha, '%Y-%m')",
        };

        $reporte = Venta::select(
            DB::raw("{$monthExpr} as mes"),
            DB::raw('COUNT(*) as total_transacciones'),
            DB::raw('SUM(total) as total_ventas')
        )
            ->groupBy('mes')
            ->orderBy('mes', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'reporte' => $reporte,
        ]);
    }

    /**
     * Resumen valorizado del inventario y alertas.
     */
    public function inventario(): JsonResponse
    {
        $resumen = Producto::select(
            DB::raw('COUNT(*) as total_referencias'),
            DB::raw('SUM(stock_actual) as total_unidades'),
            DB::raw('SUM(stock_actual * precio_compra) as valor_costo_inventario'),
            DB::raw('SUM(stock_actual * precio_venta) as valor_comercial_inventario'),
            DB::raw('SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) as productos_stock_bajo')
        )->first();

        return response()->json([
            'inventario' => [
                'total_referencias'            => (int) ($resumen->total_referencias ?? 0),
                'total_unidades'               => (int) ($resumen->total_unidades ?? 0),
                'valor_costo_inventario'       => (float) ($resumen->valor_costo_inventario ?? 0),
                'valor_comercial_inventario'   => (float) ($resumen->valor_comercial_inventario ?? 0),
                'productos_alerta_stock_bajo'  => (int) ($resumen->productos_stock_bajo ?? 0),
            ],
        ]);
    }

    /**
     * Exporta las ventas a un archivo CSV estándar descargable.
     */
    public function exportarVentasCsv(Request $request): StreamedResponse
    {
        $fileName = 'reporte_ventas_' . date('Y_m_d_His') . '.csv';

        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Numero Factura', 'Fecha', 'Metodo Pago', 'Total', 'Cliente', 'Documento', 'Usuario']);

            Venta::with('usuario')->orderBy('fecha', 'desc')->chunk(200, function ($ventas) use ($handle) {
                foreach ($ventas as $v) {
                    fputcsv($handle, [
                        $v->id,
                        $v->numero_factura,
                        $v->fecha?->format('Y-m-d H:i:s'),
                        $v->metodo_pago,
                        number_format((float) $v->total, 2, '.', ''),
                        $v->cliente_nombre ?? 'Consumidor Final',
                        $v->cliente_documento ?? 'N/A',
                        $v->usuario?->name ?? 'Sistema',
                    ]);
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$fileName}\"");

        return $response;
    }
}
