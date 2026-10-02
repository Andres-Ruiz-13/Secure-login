<?php

namespace App\Services;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class MailConfigurationService
{
    /**
     * Aplica la configuración de correo dinámica desde la base de datos si existe un registro activo.
     */
    public static function applyDynamicConfiguration(): void
    {
        try {
            if (! Schema::hasTable('mail_settings')) {
                return;
            }

            $setting = MailSetting::where('is_active', true)->latest()->first();

            if (! $setting) {
                return;
            }

            $decryptedPassword = null;
            if (! empty($setting->password)) {
                try {
                    $decryptedPassword = Crypt::decryptString($setting->password);
                } catch (\Throwable $e) {
                    $decryptedPassword = $setting->password;
                }
            }

            Config::set('mail.default', $setting->mailer ?? 'smtp');
            Config::set('mail.mailers.smtp.host', $setting->host);
            Config::set('mail.mailers.smtp.port', (int) $setting->port);
            Config::set('mail.mailers.smtp.encryption', $setting->encryption);
            Config::set('mail.mailers.smtp.username', $setting->username);
            Config::set('mail.mailers.smtp.password', $decryptedPassword);

            if (! empty($setting->from_address)) {
                Config::set('mail.from.address', $setting->from_address);
                Config::set('mail.from.name', $setting->from_name ?? config('app.name'));
            }
        } catch (\Throwable $e) {
            // Mantiene la configuración de infraestructura como fallback seguro
        }
    }
}
