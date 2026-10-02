<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Services\ImageHashService;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InbotSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $bodeguero;
    protected User $vendedor;
    protected Category $categoria;
    protected Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin_test@inbot.com',
            'role'  => 'admin',
        ]);

        $this->bodeguero = User::factory()->create([
            'email' => 'bodeguero_test@inbot.com',
            'role'  => 'bodeguero',
        ]);

        $this->vendedor = User::factory()->create([
            'email' => 'vendedor_test@inbot.com',
            'role'  => 'vendedor',
        ]);

        $this->categoria = Category::create([
            'name'      => 'Herramientas',
            'slug'      => 'herramientas',
            'is_active' => true,
        ]);

        $this->proveedor = Proveedor::create([
            'nit'       => '900999888-1',
            'nombre'    => 'Proveedor Central S.A.',
            'telefono'  => '3001112233',
            'correo'    => 'ventas@proveedorcentral.com',
            'is_active' => true,
        ]);
    }

    public function test_pricing_service_calculates_and_rounds_commercially(): void
    {
        // Precio compra: 1000, ganancia: 35% -> base 1350
        $price50 = PricingService::calculateSalePrice(1000, 35, '50');
        $this->assertEquals(1350.0, $price50);

        // Precio compra: 1032, ganancia: 20% -> 1238.4 -> redondeo a 50 -> 1250
        $priceRound50 = PricingService::calculateSalePrice(1032, 20, '50');
        $this->assertEquals(1250.0, $priceRound50);

        // Redondeo al múltiplo de 100 más cercano
        $priceRound100 = PricingService::calculateSalePrice(1032, 20, '100');
        $this->assertEquals(1200.0, $priceRound100);
    }

    public function test_unauthenticated_requests_to_products_receive_401(): void
    {
        $response = $this->getJson('/api/productos');
        $response->assertStatus(401);
    }

    public function test_vendedor_can_read_products_but_receives_403_when_creating_or_editing(): void
    {
        $producto = Producto::create([
            'codigo_barras'       => '77099990001',
            'nombre'              => 'Caja de Tornillos 1 pulgada',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'stock_actual'        => 50,
            'stock_minimo'        => 10,
            'precio_compra'       => 5000,
            'porcentaje_ganancia' => 40,
        ]);

        // 1. Vendedor puede listar productos (200 OK)
        $listResponse = $this->actingAs($this->vendedor)->getJson('/api/productos');
        $listResponse->assertStatus(200);

        // 2. Vendedor intenta crear un producto -> debe recibir 403 Forbidden
        $createResponse = $this->actingAs($this->vendedor)->postJson('/api/productos', [
            'codigo_barras'       => '77099990002',
            'nombre'              => 'Intento no autorizado',
            'precio_compra'       => 10000,
            'porcentaje_ganancia' => 30,
        ]);
        $createResponse->assertStatus(403);

        // 3. Vendedor intenta actualizar un producto -> debe recibir 403 Forbidden
        $updateResponse = $this->actingAs($this->vendedor)->putJson("/api/productos/{$producto->id}", [
            'nombre' => 'Nombre modificado por vendedor',
        ]);
        $updateResponse->assertStatus(403);

        // 4. Vendedor intenta eliminar un producto -> debe recibir 403 Forbidden
        $deleteResponse = $this->actingAs($this->vendedor)->deleteJson("/api/productos/{$producto->id}");
        $deleteResponse->assertStatus(403);
    }

    public function test_admin_or_bodeguero_can_create_product_with_automatic_price_calculation(): void
    {
        $response = $this->actingAs($this->bodeguero)->postJson('/api/productos', [
            'codigo_barras'       => '77088880001',
            'nombre'              => 'Llave Inglesa 10 pulgadas',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'stock_actual'        => 20,
            'stock_minimo'        => 5,
            'precio_compra'       => 20000,
            'porcentaje_ganancia' => 35,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('productos', [
            'codigo_barras' => '77088880001',
            'nombre'        => 'Llave Inglesa 10 pulgadas',
            'precio_venta'  => 27000.00, // 20000 * 1.35
        ]);
    }

    public function test_low_stock_endpoint_only_returns_products_at_or_below_minimum(): void
    {
        // Producto con stock normal
        Producto::create([
            'codigo_barras'       => '77011110001',
            'nombre'              => 'Producto Stock Normal',
            'categoria_id'        => $this->categoria->id,
            'stock_actual'        => 25,
            'stock_minimo'        => 10,
            'precio_compra'       => 10000,
            'porcentaje_ganancia' => 30,
        ]);

        // Producto con stock crítico
        Producto::create([
            'codigo_barras'       => '77011110002',
            'nombre'              => 'Producto Stock Crítico',
            'categoria_id'        => $this->categoria->id,
            'stock_actual'        => 3,
            'stock_minimo'        => 10,
            'precio_compra'       => 10000,
            'porcentaje_ganancia' => 30,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/stock_bajo');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('77011110002', $data[0]['codigo_barras']);
    }

    public function test_purchase_increments_stock_atomically_within_transaction(): void
    {
        $producto = Producto::create([
            'codigo_barras'       => '77022220001',
            'nombre'              => 'Disco de Corte 4.5 pulgadas',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'stock_actual'        => 10,
            'stock_minimo'        => 5,
            'precio_compra'       => 4000,
            'porcentaje_ganancia' => 40,
        ]);

        $response = $this->actingAs($this->bodeguero)->postJson('/api/compras', [
            'proveedor_id'   => $this->proveedor->id,
            'numero_factura' => 'COM-2026-001',
            'items'          => [
                [
                    'producto_id'     => $producto->id,
                    'cantidad'        => 15,
                    'precio_unitario' => 4200,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $producto->refresh();
        $this->assertEquals(25, $producto->stock_actual); // 10 + 15 = 25
        $this->assertDatabaseHas('compras', [
            'numero_factura' => 'COM-2026-001',
            'total'          => 63000.00, // 15 * 4200
        ]);
    }

    public function test_sale_with_sufficient_stock_deducts_stock_and_calculates_backend_total(): void
    {
        $producto = Producto::create([
            'codigo_barras'       => '77033330001',
            'nombre'              => 'Candado de Seguridad 50mm',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'stock_actual'        => 30,
            'stock_minimo'        => 5,
            'precio_compra'       => 10000,
            'porcentaje_ganancia' => 50, // precio_venta = 15000
        ]);

        $response = $this->actingAs($this->vendedor)->postJson('/api/ventas', [
            'metodo_pago' => 'efectivo',
            'items'       => [
                [
                    'producto_id' => $producto->id,
                    'cantidad'    => 5,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $producto->refresh();
        $this->assertEquals(25, $producto->stock_actual); // 30 - 5 = 25
        $this->assertEquals(75000.00, $response->json('venta.total')); // 5 * 15000
    }

    public function test_sale_with_insufficient_stock_fails_with_422_and_leaves_stock_untouched(): void
    {
        $producto = Producto::create([
            'codigo_barras'       => '77044440001',
            'nombre'              => 'Cerradura Digital Biométrica',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'stock_actual'        => 2,
            'stock_minimo'        => 1,
            'precio_compra'       => 150000,
            'porcentaje_ganancia' => 30,
        ]);

        // Intento de vender 5 cuando solo hay 2
        $response = $this->actingAs($this->vendedor)->postJson('/api/ventas', [
            'metodo_pago' => 'tarjeta',
            'items'       => [
                [
                    'producto_id' => $producto->id,
                    'cantidad'    => 5,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error_code' => 'INSUFFICIENT_STOCK']);

        // El stock debe permanecer intacto en 2
        $producto->refresh();
        $this->assertEquals(2, $producto->stock_actual);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_mobile_assistant_can_query_by_barcode_and_name(): void
    {
        Producto::create([
            'codigo_barras'       => '77055550001',
            'nombre'              => 'Soldadura Eléctrica 6013 1/8',
            'categoria_id'        => $this->categoria->id,
            'stock_actual'        => 40,
            'stock_minimo'        => 10,
            'precio_compra'       => 8000,
            'porcentaje_ganancia' => 35,
        ]);

        // Por código de barras
        $codeRes = $this->getJson('/api/aplicacion_movil/producto/codigo/77055550001');
        $codeRes->assertStatus(200);
        $codeRes->assertJsonPath('producto.nombre', 'Soldadura Eléctrica 6013 1/8');

        // Por nombre
        $nameRes = $this->getJson('/api/aplicacion_movil/producto/nombre?q=Soldadura');
        $nameRes->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $nameRes->json('total'));
    }

    public function test_mobile_assistant_chatbot_answers_stock_queries(): void
    {
        Producto::create([
            'codigo_barras'       => '77066660001',
            'nombre'              => 'Pulidora Angular 4.5 pulg',
            'categoria_id'        => $this->categoria->id,
            'stock_actual'        => 12,
            'stock_minimo'        => 5,
            'precio_compra'       => 90000,
            'porcentaje_ganancia' => 30,
        ]);

        $response = $this->postJson('/api/aplicacion_movil/chatbot', [
            'mensaje' => '¿Tienen existencias de la Pulidora?',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('consulta_producto', $response->json('tipo'));
        $this->assertStringContainsString('Pulidora Angular', $response->json('respuesta'));
    }

    public function test_contact_form_sends_email_and_confirmation(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contacto', [
            'name'    => 'Juan Perez',
            'email'   => 'juan@cliente.com',
            'subject' => 'Cotización de materiales',
            'message' => 'Deseo solicitar cotización para 50 bultos de cemento.',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('contact_messages', [
            'email' => 'juan@cliente.com',
            'name'  => 'Juan Perez',
        ]);

        Mail::assertSent(\App\Mail\ContactMessage::class, 2); // 1 admin + 1 confirmación usuario
    }

    public function test_reports_daily_and_monthly_sales_and_inventory_summary(): void
    {
        $producto = Producto::create([
            'codigo_barras'       => '77077770001',
            'nombre'              => 'Caja Herramientas Metalica',
            'categoria_id'        => $this->categoria->id,
            'stock_actual'        => 10,
            'stock_minimo'        => 2,
            'precio_compra'       => 50000,
            'porcentaje_ganancia' => 30, // precio_venta = 65000
        ]);

        $this->actingAs($this->admin)->postJson('/api/ventas', [
            'metodo_pago' => 'efectivo',
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ]);

        // Reporte diario
        $diarioRes = $this->actingAs($this->admin)->getJson('/api/reportes/ventas-diarias');
        $diarioRes->assertStatus(200);
        $this->assertNotEmpty($diarioRes->json('reporte'));

        // Reporte mensual
        $mensualRes = $this->actingAs($this->admin)->getJson('/api/reportes/ventas-mensuales');
        $mensualRes->assertStatus(200);
        $this->assertNotEmpty($mensualRes->json('reporte'));

        // Resumen de inventario
        $invRes = $this->actingAs($this->admin)->getJson('/api/reportes/inventario');
        $invRes->assertStatus(200);
        $this->assertGreaterThan(0, $invRes->json('inventario.total_unidades'));

        // Exportación CSV
        $csvRes = $this->actingAs($this->admin)->get('/api/reportes/exportar-ventas-csv');
        $csvRes->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $csvRes->headers->get('Content-Type'));
    }

    public function test_image_recognition_via_ahash_and_hamming_distance(): void
    {
        // 1. Verificar Hamming Distance algoritmo
        $distExacta = ImageHashService::hammingDistance('ffff0000ffff0000', 'ffff0000ffff0000');
        $this->assertEquals(0, $distExacta);

        // 1 bit diferente: f -> e (1111 vs 1110)
        $distUnBit = ImageHashService::hammingDistance('ffff0000ffff0000', 'efff0000ffff0000');
        $this->assertEquals(1, $distUnBit);

        // Hashes completamente opuestos: 64 bits distintos
        $distOpuesta = ImageHashService::hammingDistance('ffffffffffffffff', '0000000000000000');
        $this->assertEquals(64, $distOpuesta);
    }
}

