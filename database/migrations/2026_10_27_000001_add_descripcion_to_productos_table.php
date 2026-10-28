<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('productos') && ! Schema::hasColumn('productos', 'descripcion')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->text('descripcion')->nullable()->after('nombre');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('productos') && Schema::hasColumn('productos', 'descripcion')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('descripcion');
            });
        }
    }
};

