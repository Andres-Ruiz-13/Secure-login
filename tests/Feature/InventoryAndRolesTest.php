<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Compra;
use App\Models\FacturaCompra;
use App\Models\Media;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Services\ReportWordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryAndRolesTest extends TestCase
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
            'email' => 'admin_inv@test.com',
            'role'  => 'admin',
        ]);

        $this->bodeguero = User::factory()->create([
            'email' => 'bodeguero_inv@test.com',
            'role'  => 'bodeguero',
        ]);

        $this->vendedor = User::factory()->create([
            'email' => 'vendedor_inv@test.com',
            'role'  => 'vendedor',
        ]);

        $this->categoria = Category::create([
            'name'        => 'Aseo y Limpieza',
            'slug'        => 'aseo-y-limpieza',
            'description' => 'Productos de limpieza institucional',
            'is_active'   => true,
        ]);

        $this->proveedor = Proveedor::create([
            'nit'       => '800123456-7',
            'nombre'    => 'Distribuidora Global S.A.S.',
            'telefono'  => '3123456789',
            'correo'    => 'contacto@distglobal.com',
            'is_active' => true,
        ]);
    }

    public function test_matrix_roles_bodeguero_cannot_register_sales(): void
    {
        $producto = Producto::create([
            'codigo_barras'       => '77090001',
            'nombre'              => 'Jabón Antibacterial 500ml',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'stock_actual'        => 50,
            'stock_minimo'        => 5,
            'precio_compra'       => 6000,
            'porcentaje_ganancia' => 30,
        ]);

        // Bodeguero intenta registrar venta -> debe recibir 403 Forbidden
        $response = $this->actingAs($this->bodeguero)->postJson('/api/ventas', [
            'metodo_pago' => 'efectivo',
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ]);
        $response->assertStatus(403);

        // Vendedor sí puede registrar venta (201 Created)
        $respVendedor = $this->actingAs($this->vendedor)->postJson('/api/ventas', [
            'metodo_pago' => 'efectivo',
            'items'       => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ]);
        $respVendedor->assertStatus(201);
    }

    public function test_purchase_invoice_creates_new_product_and_updates_stock_in_single_step(): void
    {
        $existente = Producto::create([
            'codigo_barras'       => '77090002',
            'nombre'              => 'Limpiavidrios 1L',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'stock_actual'        => 10,
            'stock_minimo'        => 5,
            'precio_compra'       => 4000,
            'porcentaje_ganancia' => 30,
        ]);

        $response = $this->actingAs($this->bodeguero)->postJson('/api/compras', [
            'numero_factura'      => 'FAC-COMP-2026-X1',
            'proveedor_id'        => $this->proveedor->id,
            'fecha'               => '2026-10-27',
            'porcentaje_ganancia' => 30.0,
            'items'               => [
                // Fila 1: Producto existente
                [
                    'tipo'            => 'existente',
                    'producto_id'     => $existente->id,
                    'cantidad'        => 20,
                    'precio_unitario' => 4500,
                ],
                // Fila 2: Producto nuevo creado al vuelo
                [
                    'tipo'            => 'nuevo',
                    'nombre'          => 'Desinfectante de Manos 1000ml',
                    'descripcion'     => 'Elimina 99.9% de gérmenes',
                    'codigo_barras'   => '77090003',
                    'categoria_id'    => $this->categoria->id,
                    'cantidad'        => 15,
                    'precio_unitario' => 10000,
                ],
            ],
        ]);

        $response->assertStatus(201);

        // 1. Producto existente aumentó su stock a 10 + 20 = 30
        $existente->refresh();
        $this->assertEquals(30, $existente->stock_actual);
        $this->assertEquals(4500, (float) $existente->precio_compra);

        // 2. Producto nuevo fue creado con su stock y precio de venta calculado
        $nuevo = Producto::where('codigo_barras', '77090003')->first();
        $this->assertNotNull($nuevo);
        $this->assertEquals('Desinfectante de Manos 1000ml', $nuevo->nombre);
        $this->assertEquals('Elimina 99.9% de gérmenes', $nuevo->descripcion);
        $this->assertEquals(15, $nuevo->stock_actual);
        $this->assertEquals(10000, (float) $nuevo->precio_compra);
        // precio_venta = 10000 * 1.30 = 13000
        $this->assertEquals(13000, (float) $nuevo->precio_venta);

        // 3. Factura total = (20 * 4500) + (15 * 10000) = 90000 + 150000 = 240000
        $this->assertDatabaseHas('compras', [
            'numero_factura' => 'FAC-COMP-2026-X1',
            'total'          => 240000.00,
        ]);
    }

    public function test_categories_crud_permissions_matrix(): void
    {
        // 1. Vendedor puede leer categorías (200)
        $resIndex = $this->actingAs($this->vendedor)->getJson('/api/categorias');
        $resIndex->assertStatus(200);

        // 2. Vendedor no puede crear categorías (403)
        $resCreate = $this->actingAs($this->vendedor)->postJson('/api/categorias', [
            'name' => 'Ferretería Liviana',
        ]);
        $resCreate->assertStatus(403);

        // 3. Bodeguero sí puede crear categorías (201)
        $resBodeguero = $this->actingAs($this->bodeguero)->postJson('/api/categorias', [
            'nombre'      => 'Pinturas y Acabados',
            'descripcion' => 'Esmaltes y vinilos para hogar',
        ]);
        $resBodeguero->assertStatus(201);
        $this->assertDatabaseHas('categories', ['name' => 'Pinturas y Acabados']);
    }

    public function test_proveedores_crud_permissions_matrix(): void
    {
        // 1. Vendedor puede leer proveedores (200)
        $resIndex = $this->actingAs($this->vendedor)->getJson('/api/proveedores');
        $resIndex->assertStatus(200);

        // 2. Vendedor no puede crear proveedores (403)
        $resCreate = $this->actingAs($this->vendedor)->postJson('/api/proveedores', [
            'nit'    => '999111-2',
            'nombre' => 'No Autorizado',
        ]);
        $resCreate->assertStatus(403);

        // 3. Admin sí puede crear proveedores (201)
        $resAdmin = $this->actingAs($this->admin)->postJson('/api/proveedores', [
            'nit'       => '901234567-8',
            'nombre'    => 'Químicos Industriales del Valle',
            'telefono'  => '3005556677',
            'correo'    => 'ventas@quimicos.com',
            'direccion' => 'Zona Franca Bodega 4',
        ]);
        $resAdmin->assertStatus(201);
        $this->assertDatabaseHas('proveedores', ['nit' => '901234567-8']);
    }

    public function test_admin_user_management_and_role_assignment(): void
    {
        // 1. Vendedor o Bodeguero intentan acceder a /api/admin/users -> 403 Forbidden
        $this->actingAs($this->vendedor)->getJson('/api/admin/users')->assertStatus(403);
        $this->actingAs($this->bodeguero)->getJson('/api/admin/users')->assertStatus(403);

        // 2. Admin lista usuarios -> 200 OK
        $resList = $this->actingAs($this->admin)->getJson('/api/admin/users');
        $resList->assertStatus(200);

        // 3. Admin crea un nuevo usuario y lo asigna al rol 'vendedor'
        $resCreate = $this->actingAs($this->admin)->postJson('/api/admin/users', [
            'name'     => 'Pedro Cajero',
            'email'    => 'pedro.caja@inbot.com',
            'password' => 'cajero1234',
            'role'     => 'vendedor',
            'phone'    => '3159998877',
        ]);
        $resCreate->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'pedro.caja@inbot.com',
            'role'  => 'vendedor',
        ]);
    }

    public function test_core_api_stock_bajo_alias(): void
    {
        $res = $this->actingAs($this->vendedor)->getJson('/api/core-api/productos/stock_bajo');
        $res->assertStatus(200);
    }

    public function test_word_report_docx_generation(): void
    {
        $path = ReportWordService::generate('inventario');
        $this->assertFileExists($path);
        $this->assertStringEndsWith('.docx', $path);
        @unlink($path);
    }

    public function test_web_views_dashboard_and_dedicated_views_render_successfully(): void
    {
        // 1. Dashboard accesible para Administrador
        $resAdmin = $this->actingAs($this->admin)->get('/dashboard');
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Facturas Compra');
        $resAdmin->assertSee('Módulo Ventas');

        // 2. Dashboard accesible para Bodeguero
        $resBode = $this->actingAs($this->bodeguero)->get('/dashboard');
        $resBode->assertStatus(200);

        // 3. Dashboard accesible para Vendedor
        $resVend = $this->actingAs($this->vendedor)->get('/dashboard');
        $resVend->assertStatus(200);

        // 4. Vista dedicada de compras /facturas-compra-lista
        $resCompras = $this->actingAs($this->bodeguero)->get('/facturas-compra-lista');
        $resCompras->assertStatus(200);
        $resCompras->assertSee('Facturas de Compra a Proveedores');

        // 5. Vista dedicada de ventas /ventas-lista
        $resVentas = $this->actingAs($this->vendedor)->get('/ventas-lista');
        $resVentas->assertStatus(200);
        $resVentas->assertSee('Módulo de Ventas');
    }

    public function test_web_registration_assigns_role_and_enforces_permissions(): void
    {
        // 1. Registro web con rol Bodeguero
        $response = $this->post('/register', [
            'name'                  => 'Nuevo Bodeguero Web',
            'email'                 => 'nuevo.bodeguero@test.com',
            'password'              => 'Contrasena123!',
            'password_confirmation' => 'Contrasena123!',
            'role'                  => 'bodeguero',
            'phone'                 => '3112223344',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo.bodeguero@test.com',
            'role'  => 'bodeguero',
        ]);

        $nuevoBodeguero = User::where('email', 'nuevo.bodeguero@test.com')->first();
        $this->assertTrue($nuevoBodeguero->isBodeguero());
        $this->assertFalse($nuevoBodeguero->isVendedor());

        // El nuevo bodeguero puede crear productos pero no puede vender
        $this->actingAs($nuevoBodeguero)->postJson('/api/productos', [
            'codigo_barras'       => '77099998811',
            'nombre'              => 'Cinta Métrica 5m',
            'precio_compra'       => 10000,
            'porcentaje_ganancia' => 30,
        ])->assertStatus(201);

        $this->actingAs($nuevoBodeguero)->postJson('/api/ventas', [
            'metodo_pago' => 'efectivo',
            'items'       => [['producto_id' => 1, 'cantidad' => 1]],
        ])->assertStatus(403);
    }

    public function test_api_registration_assigns_role_and_returns_role_in_payload(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Nuevo Vendedor API',
            'email'                 => 'nuevo.vendedor.api@test.com',
            'password'              => 'SeguraPassword123!',
            'password_confirmation' => 'SeguraPassword123!',
            'role'                  => 'vendedor',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('user.role', 'vendedor');
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo.vendedor.api@test.com',
            'role'  => 'vendedor',
        ]);

        $nuevoVendedor = User::where('email', 'nuevo.vendedor.api@test.com')->first();
        $this->assertTrue($nuevoVendedor->isVendedor());

        // Vendedor puede vender pero no crear productos
        $this->actingAs($nuevoVendedor)->postJson('/api/productos', [
            'codigo_barras'       => '77099998822',
            'nombre'              => 'Intento Bloqueado',
            'precio_compra'       => 5000,
            'porcentaje_ganancia' => 30,
        ])->assertStatus(403);
    }

    protected function createFakeImage(string $name = 'test_item.jpg', int $sizeInKb = 50): UploadedFile
    {
        $header = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
        $paddingNeeded = max(0, ($sizeInKb * 1024) - strlen($header));
        $content = $header . str_repeat("\0", $paddingNeeded);

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_modify_product_details_and_image_via_web(): void
    {
        Storage::fake('public');

        $producto = Producto::create([
            'codigo_barras'       => '7701112223334',
            'nombre'              => 'Producto Original',
            'descripcion'         => 'Descripcion antigua',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'precio_compra'       => 20000,
            'porcentaje_ganancia' => 20,
            'stock_actual'        => 5,
            'stock_minimo'        => 2,
            'is_active'           => true,
        ]);

        $file = $this->createFakeImage('nuevo_martillo.jpg', 80);

        // 1. Bodeguero actualiza detalles y cambia imagen
        $resUpdate = $this->actingAs($this->bodeguero)->put("/cms/productos/{$producto->id}", [
            'nombre'              => 'Producto Modificado',
            'descripcion'         => 'Nueva descripción detallada',
            'codigo_barras'       => '7701112223334',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'precio_compra'       => 30000,
            'porcentaje_ganancia' => 40,
            'stock_actual'        => 15,
            'stock_minimo'        => 5,
            'is_active'           => '1',
            'imagen'              => $file,
        ]);

        $resUpdate->assertRedirect(route('dashboard'));
        $resUpdate->assertSessionHas('success');

        $producto->refresh();
        $this->assertEquals('Producto Modificado', $producto->nombre);
        $this->assertEquals('Nueva descripción detallada', $producto->descripcion);
        $this->assertEquals(30000, (float) $producto->precio_compra);
        $this->assertEquals(40, (float) $producto->porcentaje_ganancia);
        // Precio de venta calculado automáticamente: 30000 * 1.40 = 42000
        $this->assertEquals(42000, (float) $producto->precio_venta);
        $this->assertEquals(15, $producto->stock_actual);
        $this->assertNotNull($producto->imagen);
        $this->assertNotNull($producto->image_hash);

        // 2. Vendedor no tiene permiso para modificar detalles de producto
        $resVendedor = $this->actingAs($this->vendedor)->put("/cms/productos/{$producto->id}", [
            'nombre'              => 'Intento Hack Vendedor',
            'codigo_barras'       => '7701112223334',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'precio_compra'       => 100,
            'porcentaje_ganancia' => 10,
            'stock_actual'        => 100,
            'stock_minimo'        => 1,
        ]);
        $resVendedor->assertSessionHas('error');
    }

    public function test_upload_media_and_attribute_to_product_directly(): void
    {
        Storage::fake('public');

        $producto = Producto::create([
            'codigo_barras'       => '7705556667778',
            'nombre'              => 'Taladro Percutor 500W',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'precio_compra'       => 150000,
            'porcentaje_ganancia' => 30,
            'stock_actual'        => 4,
            'stock_minimo'        => 1,
        ]);

        $file = $this->createFakeImage('taladro_foto.jpg', 120);

        // Subir a biblioteca multimedia y atribuir al producto en un solo paso
        $response = $this->actingAs($this->admin)->post('/admin/media', [
            'name'        => 'Foto de Taladro',
            'file'        => $file,
            'producto_id' => $producto->id,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $media = Media::where('name', 'Foto de Taladro')->first();
        $this->assertNotNull($media);
        $this->assertEquals($producto->id, $media->producto_id);

        $producto->refresh();
        $this->assertEquals($media->path, $producto->imagen);
        $this->assertNotNull($producto->image_hash);
    }

    public function test_attribute_existing_media_to_product_and_select_from_library(): void
    {
        Storage::fake('public');

        $producto = Producto::create([
            'codigo_barras'       => '7708889990001',
            'nombre'              => 'Pulidora Angular 4-1/2',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'precio_compra'       => 120000,
            'porcentaje_ganancia' => 25,
            'stock_actual'        => 8,
            'stock_minimo'        => 2,
        ]);

        $media = Media::create([
            'name'      => 'Foto Pulidora Existente',
            'path'      => 'media/pulidora_stock.jpg',
            'mime_type' => 'image/jpeg',
            'size'      => 45000,
        ]);

        // 1. Atribuir medio existente al producto
        $resAtribuir = $this->actingAs($this->bodeguero)->post("/admin/media/{$media->id}/atribuir", [
            'producto_id' => $producto->id,
        ]);
        $resAtribuir->assertRedirect(route('dashboard'));

        $media->refresh();
        $producto->refresh();
        $this->assertEquals($producto->id, $media->producto_id);
        $this->assertEquals('media/pulidora_stock.jpg', $producto->imagen);

        // 2. Modificar producto y eliminar la imagen
        $resEliminar = $this->actingAs($this->bodeguero)->put("/cms/productos/{$producto->id}", [
            'nombre'              => 'Pulidora Angular 4-1/2',
            'codigo_barras'       => '7708889990001',
            'categoria_id'        => $this->categoria->id,
            'proveedor_id'        => $this->proveedor->id,
            'precio_compra'       => 120000,
            'porcentaje_ganancia' => 25,
            'stock_actual'        => 8,
            'stock_minimo'        => 2,
            'eliminar_imagen'     => '1',
        ]);
        $resEliminar->assertRedirect(route('dashboard'));

        $producto->refresh();
        $this->assertNull($producto->imagen);
    }
}

