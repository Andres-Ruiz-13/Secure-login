<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Enterprise News CMS, Notifications and Company Settings.
     */
    public function up(): void
    {
        // 1. Enriquecer tabla 'news' para CMS de Comunicados Internos
        if (Schema::hasTable('news')) {
            Schema::table('news', function (Blueprint $table) {
                if (! Schema::hasColumn('news', 'category')) {
                    $table->string('category', 50)->default('comunicado')->after('slug');
                }
                if (! Schema::hasColumn('news', 'priority')) {
                    $table->string('priority', 20)->default('normal')->after('category'); // baja, normal, alta, urgente
                }
                if (! Schema::hasColumn('news', 'target_role')) {
                    $table->string('target_role', 50)->default('todos')->after('priority'); // todos, admin, cajero, vendedor, bodeguero
                }
                if (! Schema::hasColumn('news', 'pinned')) {
                    $table->boolean('pinned')->default(false)->after('published'); // Fijado en el Dashboard
                }
                if (! Schema::hasColumn('news', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('pinned');
                }
                if (! Schema::hasColumn('news', 'author_id')) {
                    $table->foreignId('author_id')->nullable()->after('expires_at')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 2. Trazabilidad de Lectura de Comunicados
        if (! Schema::hasTable('news_reads')) {
            Schema::create('news_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('read_at')->useCurrent();
                $table->timestamps();

                $table->unique(['news_id', 'user_id']);
                $table->index('read_at');
            });
        }

        // 3. Parámetros Generales de la Empresa (Administrables desde el CMS)
        if (! Schema::hasTable('company_settings')) {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->id();
                $table->string('nombre_comercial')->default('INBOT SYSTEM');
                $table->string('razon_social')->default('INBOT Soluciones Empresariales S.A.S.');
                $table->string('nit', 50)->default('901.849.201-3');
                $table->string('telefono', 50)->nullable();
                $table->string('correo')->nullable();
                $table->string('direccion')->nullable();
                $table->string('ciudad', 100)->default('Bogotá, D.C.');
                $table->string('pais', 100)->default('Colombia');
                $table->string('moneda_simbolo', 10)->default('$');
                $table->string('moneda_codigo', 10)->default('COP');
                $table->string('impuesto_nombre', 20)->default('IVA');
                $table->decimal('impuesto_porcentaje', 5, 2)->default(19.00);
                $table->unsignedInteger('stock_minimo_defecto')->default(5);
                $table->string('logo_path')->nullable();
                $table->timestamps();
            });
        }

        // 4. Centro de Notificaciones y Alertas del Sistema
        if (! Schema::hasTable('system_notifications')) {
            Schema::create('system_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('target_role', 50)->default('todos'); // todos, admin, cajero, vendedor, bodeguero
                $table->string('titulo');
                $table->text('mensaje');
                $table->string('tipo', 20)->default('info'); // info, warning, danger, success
                $table->string('icono', 50)->default('bi-bell');
                $table->string('action_url')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['target_role', 'is_read']);
                $table->index(['user_id', 'is_read']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('news_reads');

        if (Schema::hasTable('news')) {
            Schema::table('news', function (Blueprint $table) {
                $columns = ['author_id', 'expires_at', 'pinned', 'target_role', 'priority', 'category'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('news', $col)) {
                        if ($col === 'author_id') {
                            $table->dropForeign(['author_id']);
                        }
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

