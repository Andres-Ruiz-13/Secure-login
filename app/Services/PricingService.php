<?php

namespace App\Services;

class PricingService
{
    /**
     * Calcula automáticamente el precio de venta en el backend.
     * Fórmula: precio_compra * (1 + porcentaje_ganancia / 100)
     * Aplica redondeo comercial (al múltiplo de 50 o 100 más cercano según config).
     */
    public static function calculateSalePrice(float $purchasePrice, float $profitPercentage, ?string $roundingMode = null): float
    {
        if ($purchasePrice < 0 || $profitPercentage < 0) {
            throw new \InvalidArgumentException('El precio de compra y porcentaje de ganancia no pueden ser negativos.');
        }

        $baseSalePrice = $purchasePrice * (1 + ($profitPercentage / 100));
        $mode = $roundingMode ?? config('inbot.rounding_mode', '50');

        return match ($mode) {
            '50' => (float) (round($baseSalePrice / 50) * 50),
            '100' => (float) (round($baseSalePrice / 100) * 100),
            default => round($baseSalePrice, 2),
        };
    }

    /**
     * Formatea un valor numérico a formato oficial de Peso Colombiano (COP).
     * Ejemplo: 114750 => "$ 114.750" (Separador de miles con punto, sin centavos)
     */
    public static function formatCop(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $val = (float) ($amount ?? 0);
        $formatted = number_format($val, 0, ',', '.');
        return $withSymbol ? '$ ' . $formatted : $formatted;
    }
}
