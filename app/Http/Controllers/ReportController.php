<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Category;
use App\Models\Compra;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Exportar reportes en formato CSV compatible con Microsoft Excel (UTF-8 con BOM)
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tipo = $request->get('tipo', 'inventario');
        $fileName = "reporte_{$tipo}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($tipo) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para que Excel reconozca tildes y caracteres especiales en español
            fputs($handle, "\xEF\xBB\xBF");

            switch ($tipo) {
                case 'inventario':
                    fputcsv($handle, ['ID', 'Código Barras', 'Producto', 'Categoría', 'Proveedor', 'Precio Compra', '% Ganancia', 'Precio Venta', 'Stock Actual', 'Stock Mínimo', 'Estado Stock', 'Activo'], ';');
                    Producto::with(['categoria', 'proveedor'])->chunk(100, function ($productos) use ($handle) {
                        foreach ($productos as $p) {
                            $estado = $p->stock_actual <= $p->stock_minimo ? 'STOCK BAJO' : 'OK';
                            fputcsv($handle, [
                                $p->id,
                                $p->codigo_barras,
                                $p->nombre,
                                $p->categoria->name ?? 'N/A',
                                $p->proveedor->nombre ?? 'N/A',
                                number_format($p->precio_compra, 0, ',', '.'),
                                $p->porcentaje_ganancia . '%',
                                number_format($p->precio_venta, 0, ',', '.'),
                                $p->stock_actual,
                                $p->stock_minimo,
                                $estado,
                                $p->is_active ? 'SÍ' : 'NO',
                            ], ';');
                        }
                    });
                    break;

                case 'ventas':
                    fputcsv($handle, ['Factura', 'Fecha', 'Hora', 'Cajero', 'Cliente', 'Documento', 'Método Pago', 'Total Facturado'], ';');
                    Venta::with('usuario')->latest('fecha')->chunk(100, function ($ventas) use ($handle) {
                        foreach ($ventas as $v) {
                            fputcsv($handle, [
                                $v->numero_factura,
                                $v->fecha ? $v->fecha->format('d/m/Y') : $v->created_at->format('d/m/Y'),
                                $v->fecha ? $v->fecha->format('H:i') : $v->created_at->format('H:i'),
                                $v->usuario->name ?? 'N/A',
                                $v->cliente_nombre ?? 'Consumidor Final',
                                $v->cliente_documento ?? 'N/A',
                                strtoupper($v->metodo_pago),
                                number_format($v->total, 0, ',', '.'),
                            ], ';');
                        }
                    });
                    break;

                case 'kardex':
                    fputcsv($handle, ['ID Movimiento', 'Fecha', 'Tipo', 'Bodega', 'Producto', 'SKU', 'Cantidad', 'Stock Anterior', 'Stock Resultante', 'Documento Ref', 'Usuario'], ';');
                    InventoryMovement::with(['warehouse', 'producto', 'user'])->latest('id')->chunk(100, function ($movs) use ($handle) {
                        foreach ($movs as $m) {
                            fputcsv($handle, [
                                $m->id,
                                $m->created_at->format('d/m/Y H:i'),
                                strtoupper($m->tipo),
                                $m->warehouse->nombre ?? 'N/A',
                                $m->producto->nombre ?? 'N/A',
                                $m->producto->codigo_barras ?? 'N/A',
                                $m->cantidad,
                                $m->stock_anterior,
                                $m->stock_resultante,
                                $m->documento_soporte ?? 'N/A',
                                $m->user->name ?? 'N/A',
                            ], ';');
                        }
                    });
                    break;

                case 'caja':
                    fputcsv($handle, ['ID Sesión', 'Caja', 'Cajero', 'Apertura', 'Cierre', 'Saldo Inicial', 'Saldo Esperado', 'Saldo Contado', 'Diferencia', 'Estado'], ';');
                    CashSession::with(['cashRegister', 'user'])->latest('opened_at')->chunk(100, function ($sessions) use ($handle) {
                        foreach ($sessions as $s) {
                            fputcsv($handle, [
                                $s->id,
                                $s->cashRegister->nombre ?? 'N/A',
                                $s->user->name ?? 'N/A',
                                $s->opened_at ? $s->opened_at->format('d/m/Y H:i') : 'N/A',
                                $s->closed_at ? $s->closed_at->format('d/m/Y H:i') : 'En curso',
                                number_format($s->saldo_inicial, 0, ',', '.'),
                                number_format($s->saldo_final_esperado ?? 0, 0, ',', '.'),
                                number_format($s->saldo_final_contado ?? 0, 0, ',', '.'),
                                number_format($s->diferencia ?? 0, 0, ',', '.'),
                                strtoupper($s->estado),
                            ], ';');
                        }
                    });
                    break;

                default:
                    fputcsv($handle, ['Error', 'Tipo de reporte desconocido'], ';');
                    break;
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

