<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Proveedores
        if (! Schema::hasTable('proveedores')) {
            Schema::create('proveedores', function (Blueprint $table) {
                $table->id();
                $table->string('nit', 50)->unique();
                $table->string('nombre');
                $table->string('telefono', 50)->nullable();
                $table->string('direccion')->nullable();
                $table->string('correo')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('nit');
            });
        }

        // 2. Productos
        if (! Schema::hasTable('productos')) {
            Schema::create('productos', function (Blueprint $table) {
                $table->id();
                $table->string('codigo_barras', 100)->unique();
                $table->string('nombre');
                $table->foreignId('categoria_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
                $table->string('imagen')->nullable();
                $table->string('image_hash', 16)->nullable(); // 64-bit Average Hash en hex
                $table->unsignedInteger('stock_actual')->default(0);
                $table->unsignedInteger('stock_minimo')->default(5);
                $table->decimal('precio_compra', 12, 2)->default(0);
                $table->decimal('porcentaje_ganancia', 8, 2)->default(30.00);
                $table->decimal('precio_venta', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('codigo_barras');
                $table->index('image_hash');
                $table->index('stock_actual');
                $table->index('stock_minimo');
                $table->index(['stock_actual', 'stock_minimo']);
            });
        }

        // 3. Compras (Entradas)
        if (! Schema::hasTable('compras')) {
            Schema::create('compras', function (Blueprint $table) {
                $table->id();
                $table->string('numero_factura', 100)->unique();
                $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnUpdate();
                $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate();
                $table->decimal('total', 12, 2)->default(0);
                $table->dateTime('fecha');
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index('numero_factura');
                $table->index('fecha');
            });
        }

        // 4. Detalle de Compras
        if (! Schema::hasTable('detalle_compras')) {
            Schema::create('detalle_compras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnUpdate();
                $table->unsignedInteger('cantidad');
                $table->decimal('precio_unitario', 12, 2);
                $table->decimal('subtotal', 12, 2);
                $table->timestamps();

                $table->index('producto_id');
            });
        }

        // 5. Ventas (Salidas / Facturación)
        if (! Schema::hasTable('ventas')) {
            Schema::create('ventas', function (Blueprint $table) {
                $table->id();
                $table->string('numero_factura', 100)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate();
                $table->string('metodo_pago', 50)->default('efectivo'); // efectivo, tarjeta, transferencia, etc.
                $table->decimal('total', 12, 2)->default(0);
                $table->dateTime('fecha');
                $table->string('cliente_nombre')->nullable();
                $table->string('cliente_documento', 50)->nullable();
                $table->timestamps();

                $table->index('numero_factura');
                $table->index('fecha');
                $table->index('user_id');
            });
        }

        // 6. Detalle de Ventas
        if (! Schema::hasTable('detalle_ventas')) {
            Schema::create('detalle_ventas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnUpdate();
                $table->unsignedInteger('cantidad');
                $table->decimal('precio_unitario', 12, 2);
                $table->decimal('subtotal', 12, 2);
                $table->timestamps();

                $table->index('producto_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('detalle_compras');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('proveedores');
    }
};
