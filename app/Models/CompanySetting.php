<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $table = 'company_settings';

    protected $fillable = [
        'nombre_comercial',
        'razon_social',
        'nit',
        'telefono',
        'correo',
        'direccion',
        'ciudad',
        'pais',
        'moneda_simbolo',
        'moneda_codigo',
        'impuesto_nombre',
        'impuesto_porcentaje',
        'stock_minimo_defecto',
        'logo_path',
    ];

    protected $casts = [
        'impuesto_porcentaje' => 'float',
        'stock_minimo_defecto' => 'integer',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'nombre_comercial' => 'INBOT SYSTEM',
            'razon_social' => 'INBOT Soluciones Empresariales S.A.S.',
            'nit' => '901.849.201-3',
            'telefono' => '+57 (601) 555-0199',
            'correo' => 'contacto@inbot.com.co',
            'direccion' => 'Av. El Dorado #68C-61',
            'ciudad' => 'Bogotá, D.C.',
            'pais' => 'Colombia',
            'moneda_simbolo' => '$',
            'moneda_codigo' => 'COP',
            'impuesto_nombre' => 'IVA',
            'impuesto_porcentaje' => 19.00,
            'stock_minimo_defecto' => 5,
        ]);
    }
}

