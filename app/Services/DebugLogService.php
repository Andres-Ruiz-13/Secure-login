<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DebugLogService
{
    /**
     * Registra un mensaje de depuración en logs/debug.log
     */
    public static function log(string $action, array $context = []): void
    {
        $logPath = base_path('logs/debug.log');
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = ! empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$timestamp}] DEBUG.{$action}:{$contextJson}" . PHP_EOL;

        @file_put_contents($logPath, $line, FILE_APPEND);

        try {
            Log::channel('debug_file')->debug("{$action}", $context);
        } catch (\Throwable $e) {
            // Silencioso si el canal falla
        }
    }
}

