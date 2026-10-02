<?php

return [
    /*
    |--------------------------------------------------------------------------
    | INBOT System - Configuración de Reglas Comerciales
    |--------------------------------------------------------------------------
    |
    | Define el criterio de redondeo comercial para el cálculo automático
    | de precio de venta en backend (precio_compra * (1 + ganancia / 100)).
    | Criterios soportados: '50' (al múltiplo de 50 más cercano),
    | '100' (al múltiplo de 100 más cercano) o 'none' (sin redondeo extra).
    |
    */
    'rounding_mode' => env('INBOT_ROUNDING_MODE', '50'), // 50, 100, none
    'default_profit_percentage' => env('INBOT_DEFAULT_PROFIT', 30.0), // %
    'low_stock_threshold' => env('INBOT_LOW_STOCK_THRESHOLD', 5),
    'max_hamming_distance' => env('INBOT_AHASH_MAX_DISTANCE', 10), // distancia máxima para coincidencia visual
];
