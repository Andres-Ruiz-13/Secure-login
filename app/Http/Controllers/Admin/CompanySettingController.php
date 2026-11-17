<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanySettingController extends Controller
{
    /**
     * Actualizar configuración corporativa de la empresa
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre_comercial'     => ['required', 'string', 'max:150'],
            'razon_social'        => ['required', 'string', 'max:180'],
            'nit'                 => ['required', 'string', 'max:50'],
            'telefono'            => ['nullable', 'string', 'max:50'],
            'correo'              => ['nullable', 'email', 'max:100'],
            'direccion'           => ['nullable', 'string', 'max:200'],
            'ciudad'              => ['nullable', 'string', 'max:80'],
            'pais'                => ['nullable', 'string', 'max:80'],
            'moneda_simbolo'      => ['required', 'string', 'max:5'],
            'moneda_codigo'       => ['required', 'string', 'max:5'],
            'impuesto_nombre'     => ['required', 'string', 'max:20'],
            'impuesto_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'stock_minimo_defecto'=> ['required', 'integer', 'min:1'],
        ]);

        $settings = CompanySetting::current();
        $settings->update($validated);

        return redirect()->route('dashboard')
            ->with('success', 'Configuración de la empresa actualizada exitosamente.');
    }
}

