<?php

namespace Tests\Feature;

use App\Models\InventoryAdjustment;
use App\Models\InventoryStock;
use App\Models\Producto;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseAndKardexTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $bodeguero;
    protected User $cajero;
    protected Warehouse $bodegaCentral;
    protected Warehouse $bodegaSecundaria;
    protected Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->bodeguero = User::factory()->create(['role' => 'bodeguero']);
        $this->cajero = User::factory()->create(['role' => 'vendedor']);

        $this->bodegaCentral = Warehouse::create([
            'codigo' => 'BOD-CENTRAL-TEST',
            'nombre' => 'Bodega Central Test',
            'is_active' => true,
        ]);

        $this->bodegaSecundaria = Warehouse::create([
            'codigo' => 'BOD-SEC-TEST',
            'nombre' => 'Bodega Sucursal Test',
            'is_active' => true,
        ]);

        $this->producto = Producto::create([
            'codigo_barras' => '770123456789',
            'nombre' => 'Pintura Vinilo Blanca',
            'precio_compra' => 50000,
            'porcentaje_ganancia' => 30,
            'stock_actual' => 20,
            'stock_minimo' => 5,
        ]);

        InventoryStock::create([
            'producto_id' => $this->producto->id,
            'warehouse_id' => $this->bodegaCentral->id,
            'stock_actual' => 20,
            'stock_minimo' => 5,
        ]);
    }

    public function test_admin_can_create_warehouse_but_vendedor_is_forbidden(): void
    {
        // 1. Cajero/vendedor es bloqueado (403)
        $resVendedor = $this->actingAs($this->cajero)->post('/bodegas', [
            'codigo' => 'BOD-NUEVA-01',
            'nombre' => 'Bodega Bloqueada',
        ]);
        $resVendedor->assertStatus(403);

        // 2. Administrador puede crear
        $resAdmin = $this->actingAs($this->admin)->post('/bodegas', [
            'codigo' => 'BOD-NUEVA-01',
            'nombre' => 'Bodega Occidente',
            'ubicacion' => 'Av Américas # 45',
            'responsable_id' => $this->bodeguero->id,
        ]);
        $resAdmin->assertRedirect(route('bodegas.index'));
        $this->assertDatabaseHas('warehouses', ['codigo' => 'BOD-NUEVA-01', 'nombre' => 'Bodega Occidente']);
    }

    public function test_full_stock_transfer_lifecycle_across_six_states(): void
    {
        // 1. Bodeguero solicita transferencia de 5 unidades de Central a Secundaria
        $resStore = $this->actingAs($this->bodeguero)->post(route('inventory.transfers.store'), [
            'origen_warehouse_id' => $this->bodegaCentral->id,
            'destino_warehouse_id' => $this->bodegaSecundaria->id,
            'observaciones' => 'Traslado para abastecer mostrador',
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 5],
            ],
        ]);
        $resStore->assertRedirect(route('bodegas.index'));

        $transfer = StockTransfer::latest('id')->first();
        $this->assertNotNull($transfer);
        $this->assertSame('pendiente', $transfer->estado);

        // 2. Administrador o Bodeguero aprueba y despacha: Pasa a "en_transito" y descuenta origen
        $resApprove = $this->actingAs($this->admin)->post(route('inventory.transfers.approve', $transfer->id));
        $resApprove->assertRedirect(route('bodegas.index'));

        $transfer->refresh();
        $this->assertSame('en_transito', $transfer->estado);

        // Origen debe tener 15
        $stockOrigen = InventoryStock::where('warehouse_id', $this->bodegaCentral->id)
            ->where('producto_id', $this->producto->id)
            ->value('stock_actual');
        $this->assertSame(15, (int) $stockOrigen);

        // 3. Bodeguero receptor confirma recepción física: Pasa a "recibida" y suma a destino
        $resReceive = $this->actingAs($this->bodeguero)->post(route('inventory.transfers.receive', $transfer->id));
        $resReceive->assertRedirect(route('bodegas.index'));

        $transfer->refresh();
        $this->assertSame('recibida', $transfer->estado);

        // Destino debe tener 5
        $stockDestino = InventoryStock::where('warehouse_id', $this->bodegaSecundaria->id)
            ->where('producto_id', $this->producto->id)
            ->value('stock_actual');
        $this->assertSame(5, (int) $stockDestino);

        // Verificar que se hayan creado los movimientos en el Kardex
        $this->assertDatabaseHas('inventory_movements', [
            'producto_id' => $this->producto->id,
            'tipo' => 'transferencia_salida',
            'cantidad' => 5,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'producto_id' => $this->producto->id,
            'tipo' => 'transferencia_entrada',
            'cantidad' => 5,
        ]);
    }

    public function test_inventory_adjustment_workflow_with_admin_approval(): void
    {
        // 1. Bodeguero detecta discrepancia física (cuenta 18 en lugar de 20: 2 mermas)
        $resStore = $this->actingAs($this->bodeguero)->post(route('inventory.adjustments.store'), [
            'warehouse_id' => $this->bodegaCentral->id,
            'producto_id' => $this->producto->id,
            'existencia_nueva' => 18,
            'motivo' => 'Tarros averiados durante reorganización de pasillo',
        ]);
        $resStore->assertRedirect(route('bodegas.index'));

        $adjustment = InventoryAdjustment::latest('id')->first();
        $this->assertNotNull($adjustment);
        $this->assertSame(-2, $adjustment->diferencia);
        $this->assertSame('pendiente', $adjustment->estado);

        // 2. Administrador aprueba el ajuste
        $resApprove = $this->actingAs($this->admin)->post(route('inventory.adjustments.approve', $adjustment->id));
        $resApprove->assertRedirect(route('bodegas.index'));

        $adjustment->refresh();
        $this->assertSame('aprobado', $adjustment->estado);

        // El stock en la bodega y el stock global deben haber bajado a 18
        $this->assertSame(18, $this->producto->fresh()->stock_actual);
        $stockBodega = InventoryStock::where('warehouse_id', $this->bodegaCentral->id)
            ->where('producto_id', $this->producto->id)
            ->value('stock_actual');
        $this->assertSame(18, (int) $stockBodega);

        // Movimiento en Kardex
        $this->assertDatabaseHas('inventory_movements', [
            'producto_id' => $this->producto->id,
            'tipo' => 'ajuste_negativo',
            'cantidad' => 2,
        ]);
    }

    public function test_bodegas_module_view_renders_for_authorized_roles(): void
    {
        $resBodeguero = $this->actingAs($this->bodeguero)->get(route('bodegas.index'));
        $resBodeguero->assertStatus(200);
        $resBodeguero->assertSee('Gestión de Bodegas, Kardex y Transferencias');

        $resAdmin = $this->actingAs($this->admin)->get(route('bodegas.index'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Nueva Bodega');

        // Cajero/Vendedor no tiene acceso a gestión de bodegas
        $resCajero = $this->actingAs($this->cajero)->get(route('bodegas.index'));
        $resCajero->assertStatus(403);
    }
}

