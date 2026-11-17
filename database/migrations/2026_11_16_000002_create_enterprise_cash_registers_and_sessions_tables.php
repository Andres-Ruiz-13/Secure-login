<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Enterprise Cash Registers, Sessions and Movements.
     */
    public function up(): void
    {
        // 1. Cajas Físicas
        if (! Schema::hasTable('cash_registers')) {
            Schema::create('cash_registers', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 50)->unique();
                $table->string('nombre');
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('codigo');
                $table->index('is_active');
            });
        }

        // 2. Sesiones de Caja (Turnos de Cajero / Apertura y Cierre)
        if (! Schema::hasTable('cash_sessions')) {
            Schema::create('cash_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cash_register_id')->constrained('cash_registers')->cascadeOnUpdate();
                $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate(); // Cajero responsable
                $table->decimal('saldo_inicial', 12, 2)->default(0);
                $table->decimal('saldo_final_esperado', 12, 2)->nullable();
                $table->decimal('saldo_final_contado', 12, 2)->nullable();
                $table->decimal('diferencia', 12, 2)->nullable(); // diferencia = contado - esperado
                $table->string('estado', 30)->default('abierta'); // abierta, cerrada
                $table->timestamp('opened_at')->useCurrent();
                $table->timestamp('closed_at')->nullable();
                $table->text('observaciones_apertura')->nullable();
                $table->text('observaciones_cierre')->nullable();
                $table->timestamps();

                $table->index('estado');
                $table->index(['user_id', 'estado']);
                $table->index('opened_at');
            });
        }

        // 3. Movimientos Directos de Efectivo en Caja (Ingresos / Retiros)
        if (! Schema::hasTable('cash_movements')) {
            Schema::create('cash_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cash_session_id')->constrained('cash_sessions')->cascadeOnDelete();
                $table->string('tipo', 20); // ingreso, retiro
                $table->decimal('monto', 12, 2);
                $table->string('motivo');
                $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate();
                $table->timestamps();

                $table->index(['cash_session_id', 'tipo']);
            });
        }

        // 4. Vincular ventas a la sesión de caja y bodega (con comprobación previa)
        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                if (! Schema::hasColumn('ventas', 'cash_session_id')) {
                    $table->foreignId('cash_session_id')->nullable()->after('user_id')->constrained('cash_sessions')->nullOnDelete();
                }
                if (! Schema::hasColumn('ventas', 'warehouse_id')) {
                    $table->foreignId('warehouse_id')->nullable()->after('cash_session_id')->constrained('warehouses')->nullOnDelete();
                }
            });
        }

        // 5. Vincular compras a la bodega destino
        if (Schema::hasTable('compras')) {
            Schema::table('compras', function (Blueprint $table) {
                if (! Schema::hasColumn('compras', 'warehouse_id')) {
                    $table->foreignId('warehouse_id')->nullable()->after('user_id')->constrained('warehouses')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('compras') && Schema::hasColumn('compras', 'warehouse_id')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
            });
        }

        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                if (Schema::hasColumn('ventas', 'warehouse_id')) {
                    $table->dropForeign(['warehouse_id']);
                    $table->dropColumn('warehouse_id');
                }
                if (Schema::hasColumn('ventas', 'cash_session_id')) {
                    $table->dropForeign(['cash_session_id']);
                    $table->dropColumn('cash_session_id');
                }
            });
        }

        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_sessions');
        Schema::dropIfExists('cash_registers');
    }
};

