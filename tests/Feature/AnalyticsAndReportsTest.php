<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cajero;
    protected Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin_rep@inbot.com',
            'role'  => 'admin',
        ]);

        $this->cajero = User::factory()->create([
            'email' => 'cajero_rep@inbot.com',
            'role'  => 'vendedor',
        ]);

        $cat = Category::create([
            'name' => 'Ferretería General',
            'slug' => 'ferreteria-general',
        ]);

        $prov = Proveedor::create([
            'nit'    => '900888777-1',
            'nombre' => 'Ferretería S.A.S.',
        ]);

        $this->producto = Producto::create([
            'codigo_barras'       => '770111222',
            'nombre'              => 'Caja de Herramientas Premium',
            'categoria_id'        => $cat->id,
            'proveedor_id'        => $prov->id,
            'precio_compra'       => 50000,
            'porcentaje_ganancia' => 30,
            'stock_actual'        => 15,
            'stock_minimo'        => 3,
            'is_active'           => true,
        ]);
    }

    public function test_authorized_user_can_export_inventory_csv(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.csv', ['tipo' => 'inventario']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Caja de Herramientas Premium', $response->streamedContent());
        $this->assertStringContainsString('770111222', $response->streamedContent());
    }

    public function test_authorized_user_can_export_sales_csv(): void
    {
        Venta::create([
            'numero_factura'    => 'FAC-TEST-001',
            'user_id'           => $this->cajero->id,
            'total'             => 65000.00,
            'metodo_pago'       => 'efectivo',
            'fecha'             => now(),
            'cliente_nombre'    => 'Carlos Gómez',
            'cliente_documento' => '12345678',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.csv', ['tipo' => 'ventas']));

        $response->assertStatus(200);
        $this->assertStringContainsString('FAC-TEST-001', $response->streamedContent());
        $this->assertStringContainsString('Carlos Gómez', $response->streamedContent());
    }

    public function test_dashboard_renders_analytics_and_charts(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Inteligencia y Analítica de Negocio');
        $response->assertSee('chartSalesTrend');
        $response->assertSee('chartStockCategory');
    }
}

