<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\CompanySetting;
use App\Models\InventoryStock;
use App\Models\News;
use App\Models\Producto;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_attributes_and_distinctive_colors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bodeguero = User::factory()->create(['role' => 'bodeguero']);
        $cajero = User::factory()->create(['role' => 'vendedor']);

        // 1. Administrador (Rojo carmesí)
        $this->assertTrue($admin->isAdmin());
        $this->assertSame('Administrador', $admin->role_title);
        $this->assertSame('#dc2626', $admin->role_color);
        $this->assertStringContainsString('bg-danger', $admin->role_badge_class);
        $this->assertSame('bi-shield-lock-fill', $admin->role_icon);

        // 2. Bodeguero (Azul océano)
        $this->assertTrue($bodeguero->isBodeguero());
        $this->assertSame('Bodeguero', $bodeguero->role_title);
        $this->assertSame('#2563eb', $bodeguero->role_color);
        $this->assertStringContainsString('bg-primary', $bodeguero->role_badge_class);
        $this->assertSame('bi-boxes', $bodeguero->role_icon);

        // 3. Cajero / Vendedor (Verde esmeralda)
        $this->assertTrue($cajero->isVendedor());
        $this->assertTrue($cajero->isCajero());
        $this->assertSame('Cajero / Punto de Venta', $cajero->role_title);
        $this->assertSame('#059669', $cajero->role_color);
        $this->assertStringContainsString('bg-success', $cajero->role_badge_class);
        $this->assertSame('bi-cash-coin', $cajero->role_icon);
    }

    public function test_warehouses_and_inventory_stock_distribution(): void
    {
        $warehouse = Warehouse::create([
            'codigo' => 'BOD-TEST-01',
            'nombre' => 'Bodega de Prueba 1',
            'is_active' => true,
        ]);

        $producto = Producto::create([
            'codigo_barras' => '7709999901',
            'nombre' => 'Taladro Percutor Industrial',
            'precio_compra' => 100000,
            'porcentaje_ganancia' => 30,
            'stock_actual' => 15,
            'stock_minimo' => 5,
        ]);

        $stock = InventoryStock::create([
            'producto_id' => $producto->id,
            'warehouse_id' => $warehouse->id,
            'stock_actual' => 10,
            'stock_minimo' => 3,
            'ubicacion_pasillo' => 'Pasillo 4B',
        ]);

        $this->assertSame(10, $producto->stockEnBodega($warehouse->id));
        $this->assertSame($producto->id, $stock->producto->id);
        $this->assertSame($warehouse->id, $stock->warehouse->id);
    }

    public function test_cash_register_and_cash_session_lifecycle(): void
    {
        $cajero = User::factory()->create(['role' => 'vendedor']);

        $caja = CashRegister::create([
            'codigo' => 'CAJA-POS-01',
            'nombre' => 'Caja Mostrador Norte',
            'is_active' => true,
        ]);

        // Apertura de caja
        $session = CashSession::create([
            'cash_register_id' => $caja->id,
            'user_id' => $cajero->id,
            'saldo_inicial' => 50000.00,
            'estado' => 'abierta',
            'opened_at' => now(),
            'observaciones_apertura' => 'Base inicial entregada en efectivo.',
        ]);

        $this->assertSame('abierta', $session->estado);
        $this->assertSame($cajero->id, $session->user->id);
        $this->assertSame($caja->id, $cajero->currentCashSession->cash_register_id);

        // Cierre de caja con arqueo
        $session->update([
            'saldo_final_esperado' => 150000.00,
            'saldo_final_contado' => 150000.00,
            'diferencia' => 0.00,
            'estado' => 'cerrada',
            'closed_at' => now(),
            'observaciones_cierre' => 'Cuadre perfecto sin novedades.',
        ]);

        $this->assertSame('cerrada', $session->fresh()->estado);
        $this->assertNull($cajero->fresh()->currentCashSession);
    }

    public function test_cms_news_priority_and_role_targeting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $newsCajero = News::create([
            'title' => 'Instrucción de Cuadre de Caja',
            'slug' => 'instruccion-caja-test',
            'category' => 'procedimiento',
            'priority' => 'urgente',
            'target_role' => 'vendedor',
            'content' => 'Detalle del procedimiento...',
            'published' => true,
            'pinned' => true,
            'author_id' => $admin->id,
        ]);

        $newsBodeguero = News::create([
            'title' => 'Recepción de Proveedores Martes',
            'slug' => 'recepcion-bodega-test',
            'category' => 'alerta',
            'priority' => 'alta',
            'target_role' => 'bodeguero',
            'content' => 'Llegada de camiones a primera hora...',
            'published' => true,
            'pinned' => false,
            'author_id' => $admin->id,
        ]);

        // Filtrado por roles
        $paraCajeros = News::paraRol('vendedor')->get();
        $this->assertTrue($paraCajeros->contains($newsCajero));
        $this->assertFalse($paraCajeros->contains($newsBodeguero));

        $paraBodegueros = News::paraRol('bodeguero')->get();
        $this->assertTrue($paraBodegueros->contains($newsBodeguero));
        $this->assertFalse($paraBodegueros->contains($newsCajero));
    }

    public function test_company_settings_singleton_structure(): void
    {
        $settings = CompanySetting::current();

        $this->assertNotNull($settings);
        $this->assertSame('INBOT SYSTEM', $settings->nombre_comercial);
        $this->assertSame('$', $settings->moneda_simbolo);
        $this->assertSame('COP', $settings->moneda_codigo);
    }
}
