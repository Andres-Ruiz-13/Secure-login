<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Mail Settings (SMTP administrable cifrado)
        if (! Schema::hasTable('mail_settings')) {
            Schema::create('mail_settings', function (Blueprint $table) {
                $table->id();
                $table->string('mailer')->default('smtp');
                $table->string('host');
                $table->integer('port')->default(587);
                $table->string('encryption')->nullable()->default('tls');
                $table->string('username')->nullable();
                $table->text('password')->nullable(); // Cifrado con Crypt
                $table->string('from_address')->nullable();
                $table->string('from_name')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Password Reset Codes (OTP)
        if (! Schema::hasTable('password_reset_codes')) {
            Schema::create('password_reset_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('code_hash'); // Hash seguro del código OTP
                $table->timestamp('expires_at');
                $table->integer('attempts')->default(0);
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'expires_at']);
            });
        }

        // 3. Audit Logs (Auditoría de seguridad y trazabilidad)
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event'); // login, logout, product_created, sale_completed, etc.
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['event', 'created_at']);
                $table->index(['model_type', 'model_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('password_reset_codes');
        Schema::dropIfExists('mail_settings');
    }
};
