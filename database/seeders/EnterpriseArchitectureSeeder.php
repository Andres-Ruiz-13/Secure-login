<?php

namespace Database\Seeders;

use App\Models\CashRegister;
use App\Models\CompanySetting;
use App\Models\InventoryStock;
use App\Models\News;
use App\Models\Producto;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnterpriseArchitectureSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Asegurar Parámetros de la Empresa
        CompanySetting::current();

        // 2. Administrador y Bodeguero responsable
        $admin = User::where('role', 'admin')->first();
        $bodeguero = User::where('role', 'bodeguero')->first();
        $cajero = User::whereIn('role', ['cajero', 'vendedor'])->first();

        // 3. Crear Bodegas Principales
        $bodegaCentral = Warehouse::firstOrCreate(
            ['codigo' => 'BOD-CENTRAL'],
            [
                'nombre' => 'Bodega Central de Almacenamiento',
                'ubicacion' => 'Sede Principal - Nave A',
                'descripcion' => 'Almacén general de recepción de compras y distribución de mercancías.',
                'responsable_id' => $bodeguero?->id ?? $admin?->id,
                'is_active' => true,
            ]
        );

        $bodegaPuntoVenta = Warehouse::firstOrCreate(
            ['codigo' => 'BOD-VENTAS'],
            [
                'nombre' => 'Bodega de Exhibición y Ventas',
                'ubicacion' => 'Punto de Venta Local 101',
                'descripcion' => 'Existencias en góndolas y mostrador para atención inmediata en caja.',
                'responsable_id' => $bodeguero?->id ?? $admin?->id,
                'is_active' => true,
            ]
        );

        // 4. Asignar usuarios a bodegas
        if ($bodeguero) {
            $bodegaCentral->users()->syncWithoutDetaching([$bodeguero->id]);
            $bodegaPuntoVenta->users()->syncWithoutDetaching([$bodeguero->id]);
        }

        // 5. Crear Cajas Físicas
        CashRegister::firstOrCreate(
            ['codigo' => 'CAJA-01'],
            [
                'nombre' => 'Caja Principal Mostrador 1',
                'warehouse_id' => $bodegaPuntoVenta->id,
                'is_active' => true,
            ]
        );

        CashRegister::firstOrCreate(
            ['codigo' => 'CAJA-02'],
            [
                'nombre' => 'Caja Rápida Mostrador 2',
                'warehouse_id' => $bodegaPuntoVenta->id,
                'is_active' => true,
            ]
        );

        // 6. Poblar inventario inicial por bodega para productos existentes
        $productos = Producto::all();
        foreach ($productos as $producto) {
            InventoryStock::firstOrCreate(
                [
                    'producto_id' => $producto->id,
                    'warehouse_id' => $bodegaCentral->id,
                ],
                [
                    'stock_actual' => $producto->stock_actual,
                    'stock_minimo' => $producto->stock_minimo,
                    'ubicacion_pasillo' => 'Pasillo Central A-' . ($producto->id % 10 + 1),
                ]
            );

            // Existencias iniciales en bodega de punto de venta (muestra/vitrina)
            InventoryStock::firstOrCreate(
                [
                    'producto_id' => $producto->id,
                    'warehouse_id' => $bodegaPuntoVenta->id,
                ],
                [
                    'stock_actual' => min(5, (int) $producto->stock_actual),
                    'stock_minimo' => 2,
                    'ubicacion_pasillo' => 'Góndola V-' . ($producto->id % 5 + 1),
                ]
            );
        }

        // 7. Comunicados y Noticias iniciales en el CMS priorizados por rol
        News::firstOrCreate(
            ['slug' => 'nuevo-protocolo-de-seguridad-y-apertura-de-caja'],
            [
                'title' => 'Protocolo de Seguridad: Registro y Arqueo de Cajas',
                'category' => 'procedimiento',
                'priority' => 'alta',
                'target_role' => 'vendedor',
                'pinned' => true,
                'excerpt' => 'Nuevo estándar obligatorio para apertura, cobro y cierre de caja en cada turno.',
                'content' => 'Se informa a todo el personal de cajas que a partir de la fecha es obligatorio realizar la apertura de turno con el saldo inicial verificado y el cierre con conteo físico detallado.',
                'published' => true,
                'author_id' => $admin?->id,
            ]
        );

        News::firstOrCreate(
            ['slug' => 'lineamientos-para-transferencias-entre-bodegas'],
            [
                'title' => 'Lineamientos para Transferencias de Mercancía entre Bodegas',
                'category' => 'alerta',
                'priority' => 'urgente',
                'target_role' => 'bodeguero',
                'pinned' => true,
                'excerpt' => 'Toda transferencia entre bodegas requiere autorización y confirmación de recepción en el sistema.',
                'content' => 'Las salidas de inventario por transferencia no incrementarán las existencias de la bodega de destino hasta que el bodeguero receptor confirme físicamente el arribo de la mercancía.',
                'published' => true,
                'author_id' => $admin?->id,
            ]
        );

        News::firstOrCreate(
            ['slug' => 'balance-operativo-y-auditoria-de-seguridad-aprobada'],
            [
                'title' => 'Balance Operativo: Plataforma Empresarial Auditada con Éxito',
                'category' => 'comunicado',
                'priority' => 'normal',
                'target_role' => 'todos',
                'pinned' => false,
                'excerpt' => 'El sistema INBOT ha superado las pruebas de seguridad OWASP ZAP y control de existencias.',
                'content' => 'Felicitamos a todos los equipos administrativos, de bodega y caja por la implementación de los nuevos protocolos de seguridad, CSP estricto y trazabilidad de inventarios.',
                'published' => true,
                'author_id' => $admin?->id,
            ]
        );

        // 8. Notificaciones de inicio para el panel
        SystemNotification::firstOrCreate(
            ['titulo' => 'Módulo Multi-Bodega Activado'],
            [
                'mensaje' => 'Se han configurado las bodegas Central y Ventas con control de transferencias y existencias.',
                'target_role' => 'admin',
                'tipo' => 'success',
                'icono' => 'bi-building-check',
                'action_url' => '/dashboard',
            ]
        );

        SystemNotification::firstOrCreate(
            ['titulo' => 'Control de Stock Bajo'],
            [
                'mensaje' => 'Hay productos que requieren reposición en la bodega principal.',
                'target_role' => 'bodeguero',
                'tipo' => 'warning',
                'icono' => 'bi-exclamation-triangle-fill',
                'action_url' => '/dashboard',
            ]
        );
    }
}

