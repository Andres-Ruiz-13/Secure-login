<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Claves sensibles que nunca deben ser almacenadas en los logs de auditoría.
     */
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'token',
        'plainTextToken',
        'otp',
        'code_hash',
        'smtp_password',
        'api_key',
        'secret',
        'authorization',
    ];

    /**
     * Registra un evento de auditoría en la base de datos de forma segura.
     */
    public static function log(
        string $event,
        ?string $modelType = null,
        ?int $modelId = null,
        array $metadata = [],
        ?int $userId = null
    ): ?AuditLog {
        try {
            $sanitizedMetadata = self::sanitize($metadata);

            return AuditLog::create([
                'user_id'    => $userId ?? Auth::id(),
                'event'      => $event,
                'model_type' => $modelType,
                'model_id'   => $modelId,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => substr(Request::userAgent() ?? 'CLI/System', 0, 500),
                'metadata'   => $sanitizedMetadata,
            ]);
        } catch (\Throwable $e) {
            // La auditoría no debe romper la ejecución principal si hay fallos
            return null;
        }
    }

    /**
     * Sanitiza metadatos recursivamente eliminando claves confidenciales.
     */
    public static function sanitize(array $data): array
    {
        $cleaned = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), self::$sensitiveKeys, true)) {
                $cleaned[$key] = '********';
            } elseif (is_array($value)) {
                $cleaned[$key] = self::sanitize($value);
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }
}
