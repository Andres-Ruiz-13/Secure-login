<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InbotSystemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuarios con roles (Admin, Bodeguero, Vendedor)
        $admin = User::firstOrCreate(
            ['email' => 'admin@inbot.com'],
            [
                'name'     => 'Administrador General',
                'password' => Hash::make('Segura123!'),
                'role'     => 'admin',
                'phone'    => '3001234567',
            ]
        );

        $bodeguero = User::firstOrCreate(
            ['email' => 'bodeguero@inbot.com'],
            [
                'name'     => 'Carlos Bodeguero',
                'password' => Hash::make('Segura123!'),
                'role'     => 'bodeguero',
                'phone'    => '3007654321',
            ]
        );

        $vendedor = User::firstOrCreate(
            ['email' => 'vendedor@inbot.com'],
            [
                'name'     => 'Ana Vendedora',
                'password' => Hash::make('Segura123!'),
                'role'     => 'vendedor',
                'phone'    => '3109876543',
            ]
        );

        // 2. Categorías
        $catHerramientas = Category::firstOrCreate(
            ['slug' => 'herramientas-electricas-manuales'],
            ['name' => 'Herramientas Eléctricas y Manuales', 'description' => 'Maquinaria y accesorios de taller', 'is_active' => true]
        );

        $catPinturas = Category::firstOrCreate(
            ['slug' => 'pinturas-y-acabados'],
            ['name' => 'Pinturas y Acabados', 'description' => 'Vinilos, esmaltes y solventes', 'is_active' => true]
        );

        $catElectricidad = Category::firstOrCreate(
            ['slug' => 'electricidad-e-iluminacion'],
            ['name' => 'Electricidad e Iluminación', 'description' => 'Cables, iluminación y switches', 'is_active' => true]
        );

        // 3. Proveedores
        $prov1 = Proveedor::firstOrCreate(
            ['nit' => '900123456-1'],
            [
                'nombre'    => 'DistriHerramientas S.A.S.',
                'telefono'  => '601-5551234',
                'direccion' => 'Calle Industrial # 45-20',
                'correo'    => 'ventas@distriherramientas.com',
                'is_active' => true,
            ]
        );

        $prov2 = Proveedor::firstOrCreate(
            ['nit' => '890987654-2'],
            [
                'nombre'    => 'Pinturas del Valle Ltda.',
                'telefono'  => '602-8889900',
                'direccion' => 'Av. Central # 12-80',
                'correo'    => 'comercial@pinturasdelvalle.com',
                'is_active' => true,
            ]
        );

        // 4. Productos con cálculo automático de precio y aHash precalculado
        $productosData = [
            [
                'codigo_barras'       => '7701234567890',
                'nombre'              => 'Taladro Percutor 1/2 650W',
                'categoria_id'        => $catHerramientas->id,
                'proveedor_id'        => $prov1->id,
                'stock_actual'        => 15,
                'stock_minimo'        => 5,
                'precio_compra'       => 120000.00,
                'porcentaje_ganancia' => 35.00,
                'image_hash'          => 'ff818181818181ff', // aHash de prueba indexado
            ],
            [
                'codigo_barras'       => '7701234567891',
                'nombre'              => 'Martillo de Uña 16oz Acero',
                'categoria_id'        => $catHerramientas->id,
                'proveedor_id'        => $prov1->id,
                'stock_actual'        => 3, // ALERTA: stock bajo (3 <= 8)
                'stock_minimo'        => 8,
                'precio_compra'       => 18500.00,
                'porcentaje_ganancia' => 40.00,
                'image_hash'          => 'e070381c0e070381',
            ],
            [
                'codigo_barras'       => '7701234567892',
                'nombre'              => 'Juego Destornilladores 6 Piezas',
                'categoria_id'        => $catHerramientas->id,
                'proveedor_id'        => $prov1->id,
                'stock_actual'        => 22,
                'stock_minimo'        => 6,
                'precio_compra'       => 24000.00,
                'porcentaje_ganancia' => 30.00,
                'image_hash'          => '003c7e7e7e7e3c00',
            ],
            [
                'codigo_barras'       => '7701234567893',
                'nombre'              => 'Cinta Aislante Negra 20m',
                'categoria_id'        => $catElectricidad->id,
                'proveedor_id'        => $prov1->id,
                'stock_actual'        => 2, // ALERTA: stock bajo (2 <= 10)
                'stock_minimo'        => 10,
                'precio_compra'       => 3200.00,
                'porcentaje_ganancia' => 45.00,
                'image_hash'          => 'aa55aa55aa55aa55',
            ],
            [
                'codigo_barras'       => '7701234567894',
                'nombre'              => 'Galón Pintura Vinilo Blanco',
                'categoria_id'        => $catPinturas->id,
                'proveedor_id'        => $prov2->id,
                'stock_actual'        => 14,
                'stock_minimo'        => 4,
                'precio_compra'       => 48000.00,
                'porcentaje_ganancia' => 28.00,
                'image_hash'          => '0f0f0f0f0f0f0f0f',
            ],
        ];

        foreach ($productosData as $p) {
            Producto::firstOrCreate(
                ['codigo_barras' => $p['codigo_barras']],
                array_merge($p, [
                    'precio_venta' => PricingService::calculateSalePrice($p['precio_compra'], $p['porcentaje_ganancia']),
                    'is_active'    => true,
                ])
            );
        }

        // 5. Banners Hero
        Banner::firstOrCreate(
            ['title' => 'Gestión Automatizada de Inventarios y Ventas'],
            [
                'subtitle'    => 'Control de existencias, facturación inmediata y reconocimiento de productos en tiempo real.',
                'button_text' => 'Explorar Catálogo',
                'button_url'  => '#productos',
                'position'    => 1,
                'is_active'   => true,
                'created_by'  => $admin->id,
            ]
        );

        // 6. Testimonios
        Testimonial::firstOrCreate(
            ['name' => 'Ferretería El Progreso'],
            [
                'position_or_company' => 'Gerente de Operaciones',
                'testimonial'         => 'INBOT System optimizó el control de bodega y redujo las mermas a cero gracias a las alertas de stock.',
                'rating'              => 5,
                'position'            => 1,
                'is_active'           => true,
            ]
        );

        // 7. Configuración general
        SiteSetting::set('site_name', 'INBOT System');
        SiteSetting::set('contact_email', 'contacto@inbot.com');
        SiteSetting::set('contact_phone', '+57 (1) 555-0199');
    }
}
