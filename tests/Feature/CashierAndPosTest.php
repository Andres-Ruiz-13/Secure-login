<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierAndPosTest extends TestCase
{
    use RefreshDatabase;

    protected User $cajero;
    protected User $bodeguero;
    protected User $admin;
    protected Warehouse $warehouse;
    protected CashRegister $register;
    protected Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin_pos@inbot.com',
            'role'  => 'admin',
        ]);

        $this->cajero = User::factory()->create([
            'email' => 'cajero_pos@inbot.com',
            'role'  => 'vendedor',
        ]);

        $this->bodeguero = User::factory()->create([
            'email' => 'bodega_pos@inbot.com',
            'role'  => 'bodeguero',
        ]);

        $this->warehouse = Warehouse::create([
            'codigo' => 'BOD-POS',
            'nombre' => 'Bodega POS Central',
            'is_active' => true,
        ]);

        $this->register = CashRegister::create([
            'codigo' => 'CAJA-01',
            'nombre' => 'Caja Principal Mostrador',
            'warehouse_id' => $this->warehouse->id,
            'is_active' => true,
        ]);

        $cat = Category::create([
            'name' => 'Ferretería POS',
            'slug' => 'ferreteria-pos',
        ]);

        $prov = Proveedor::create([
            'nit' => '900111222-1',
            'nombre' => 'Distribuidor Ferretero',
            'is_active' => true,
        ]);

        $this->producto = Producto::create([
            'codigo_barras' => '770999001',
            'nombre' => 'Taladro Percutor 500W',
            'categoria_id' => $cat->id,
            'proveedor_id' => $prov->id,
            'precio_compra' => 100000,
            'porcentaje_ganancia' => 30,
            'stock_actual' => 20,
            'stock_minimo' => 5,
            'is_active' => true,
        ]);
    }

    public function test_cajero_can_open_cash_session(): void
    {
        $response = $this->actingAs($this->cajero)->post(route('cash.session.open'), [
            'cash_register_id' => $this->register->id,
            'saldo_inicial'    => 50000.00,
            'observaciones'    => 'Apertura turno de la mañana',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_sessions', [
            'cash_register_id' => $this->register->id,
            'user_id'          => $this->cajero->id,
            'estado'           => 'abierta',
            'saldo_inicial'    => 50000.00,
        ]);

        // Intentar abrir una segunda sesión sin cerrar la primera debe ser rechazado
        $second = $this->actingAs($this->cajero)->post(route('cash.session.open'), [
            'cash_register_id' => $this->register->id,
            'saldo_inicial'    => 20000.00,
        ]);

        $second->assertRedirect();
        $second->assertSessionHas('error');
    }

    public function test_cajero_can_register_cash_movements(): void
    {
        $session = CashSession::create([
            'cash_register_id' => $this->register->id,
            'user_id'          => $this->cajero->id,
            'apertura_at'      => now(),
            'saldo_inicial'    => 100000.00,
            'estado'           => 'abierta',
        ]);

        // Registrar ingreso extraordinario
        $ingreso = $this->actingAs($this->cajero)->post(route('cash.session.movement', $session->id), [
            'tipo'   => 'ingreso',
            'monto'  => 15000.00,
            'motivo' => 'Monedas para cambio',
        ]);

        $ingreso->assertRedirect();
        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $session->id,
            'tipo'            => 'ingreso',
            'monto'           => 15000.00,
        ]);

        // Registrar egreso extraordinario
        $egreso = $this->actingAs($this->cajero)->post(route('cash.session.movement', $session->id), [
            'tipo'   => 'egreso',
            'monto'  => 5000.00,
            'motivo' => 'Compra de cinta adhesiva de empaque',
        ]);

        $egreso->assertRedirect();
        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $session->id,
            'tipo'            => 'egreso',
            'monto'           => 5000.00,
        ]);
    }

    public function test_sale_links_to_active_cash_session_and_deducts_stock(): void
    {
        $session = CashSession::create([
            'cash_register_id' => $this->register->id,
            'user_id'          => $this->cajero->id,
            'apertura_at'      => now(),
            'saldo_inicial'    => 50000.00,
            'estado'           => 'abierta',
        ]);

        $resVenta = $this->actingAs($this->cajero)->post(route('cms.ventas.store'), [
            'cliente_nombre'    => 'Juan Pérez',
            'cliente_documento' => '10203040',
            'metodo_pago'       => 'efectivo',
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad'    => 2,
                ],
            ],
        ]);

        $resVenta->assertRedirect();
        $this->assertDatabaseHas('ventas', [
            'user_id'         => $this->cajero->id,
            'cash_session_id' => $session->id,
            'cliente_nombre'  => 'Juan Pérez',
        ]);

        $this->producto->refresh();
        $this->assertEquals(18, $this->producto->stock_actual);
    }

    public function test_cajero_can_close_cash_session_with_arqueo(): void
    {
        $session = CashSession::create([
            'cash_register_id' => $this->register->id,
            'user_id'          => $this->cajero->id,
            'apertura_at'      => now(),
            'saldo_inicial'    => 100000.00,
            'estado'           => 'abierta',
        ]);

        // Ingreso extra: +20,000
        CashMovement::create([
            'cash_session_id' => $session->id,
            'user_id'         => $this->cajero->id,
            'tipo'            => 'ingreso',
            'monto'           => 20000.00,
            'motivo'          => 'Aporte inicial caja',
        ]);

        // Saldo esperado: 100,000 + 20,000 = 120,000
        // Si el cajero cuenta 122,000 (sobrante de 2,000)
        $response = $this->actingAs($this->cajero)->post(route('cash.session.close', $session->id), [
            'saldo_final_real' => 122000.00,
            'observaciones'    => 'Arqueo realizado con sobrante de $2,000',
        ]);

        $response->assertRedirect();
        $session->refresh();

        $this->assertEquals('cerrada', $session->estado);
        $this->assertEquals(120000.00, (float) $session->saldo_final_esperado);
        $this->assertEquals(122000.00, (float) $session->saldo_final_real);
        $this->assertEquals(2000.00, (float) $session->diferencia);
    }

    public function test_bodeguero_cannot_access_cash_or_sales(): void
    {
        // Bodeguero intenta abrir caja -> 403
        $resOpen = $this->actingAs($this->bodeguero)->post(route('cash.session.open'), [
            'cash_register_id' => $this->register->id,
            'saldo_inicial'    => 10000.00,
        ]);
        $resOpen->assertStatus(403);

        // Bodeguero intenta registrar venta web -> Denegado con mensaje o 403
        $resVenta = $this->actingAs($this->bodeguero)->post(route('cms.ventas.store'), [
            'metodo_pago' => 'efectivo',
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 1],
            ],
        ]);
        $resVenta->assertSessionHas('error');
    }

    public function test_admin_and_cajero_can_access_ventas_lista_without_lazy_loading_violations(): void
    {
        // 1. Admin accede a /ventas-lista
        $resAdmin = $this->actingAs($this->admin)->get('/ventas-lista');
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Módulo de Ventas');

        // 2. Cajero accede a /ventas-lista
        $resCajero = $this->actingAs($this->cajero)->get('/ventas-lista');
        $resCajero->assertStatus(200);
        $resCajero->assertSee('Módulo de Ventas');
    }
}

