<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Enterprise Warehouses, Stocks, Transfers and Adjustments.
     */
    public function up(): void
    {
        // 1. Bodegas
        if (! Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 50)->unique();
                $table->string('nombre');
                $table->string('ubicacion')->nullable();
                $table->text('descripcion')->nullable();
                $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('codigo');
                $table->index('is_active');
            });
        }

        // 2. Asignación de Usuarios a Bodegas
        if (! Schema::hasTable('warehouse_users')) {
            Schema::create('warehouse_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['warehouse_id', 'user_id']);
            });
        }

        // 3. Existencias por Bodega
        if (! Schema::hasTable('inventory_stocks')) {
            Schema::create('inventory_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->unsignedInteger('stock_actual')->default(0);
                $table->unsignedInteger('stock_minimo')->default(5);
                $table->string('ubicacion_pasillo', 100)->nullable();
                $table->timestamps();

                $table->unique(['producto_id', 'warehouse_id']);
                $table->index(['stock_actual', 'stock_minimo']);
            });
        }

        // 4. Movimientos Transaccionales de Inventario (Kardex)
        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('tipo', 50); // compra, venta, entrada_ajuste, salida_ajuste, transferencia_origen, transferencia_destino, merma, devolucion
                $table->unsignedInteger('cantidad');
                $table->unsignedInteger('stock_anterior');
                $table->unsignedInteger('stock_nuevo');
                $table->string('documento_referencia', 100)->nullable(); // Ej: FAC-COMP-901, FAC-VENTA-001, TRF-001
                $table->text('motivo')->nullable();
                $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate();
                $table->timestamps();

                $table->index('tipo');
                $table->index('documento_referencia');
                $table->index(['producto_id', 'warehouse_id']);
                $table->index('created_at');
            });
        }

        // 5. Transferencias entre Bodegas (Flujo de 6 Estados)
        if (! Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 50)->unique();
                $table->foreignId('origen_warehouse_id')->constrained('warehouses')->cascadeOnUpdate();
                $table->foreignId('destino_warehouse_id')->constrained('warehouses')->cascadeOnUpdate();
                $table->string('estado', 30)->default('pendiente'); // pendiente, aprobada, en_transito, recibida, rechazada, cancelada
                $table->foreignId('solicitado_por')->constrained('users')->cascadeOnUpdate();
                $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('recibido_por')->nullable()->constrained('users')->nullOnDelete();
                $table->text('observaciones')->nullable();
                $table->timestamp('aprobada_at')->nullable();
                $table->timestamp('recibida_at')->nullable();
                $table->timestamps();

                $table->index('codigo');
                $table->index('estado');
            });
        }

        // 6. Líneas / Productos de una Transferencia
        if (! Schema::hasTable('stock_transfer_items')) {
            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnUpdate();
                $table->unsignedInteger('cantidad');
                $table->timestamps();

                $table->index(['transfer_id', 'producto_id']);
            });
        }

        // 7. Ajustes de Inventario Físico vs Digital (Requiere aprobación admin)
        if (! Schema::hasTable('inventory_adjustments')) {
            Schema::create('inventory_adjustments', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 50)->unique();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnUpdate();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnUpdate();
                $table->unsignedInteger('existencia_anterior');
                $table->unsignedInteger('existencia_nueva');
                $table->integer('diferencia'); // Puede ser negativo (pérdida/merma) o positivo (sobrante)
                $table->text('motivo');
                $table->string('estado', 30)->default('pendiente'); // pendiente, aprobado, rechazado
                $table->foreignId('solicitado_por')->constrained('users')->cascadeOnUpdate();
                $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('autorizado_at')->nullable();
                $table->timestamps();

                $table->index('codigo');
                $table->index('estado');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('warehouse_users');
        Schema::dropIfExists('warehouses');
    }
};
