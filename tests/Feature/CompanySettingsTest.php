<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $bodeguero;
    protected User $cajero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin_cfg@inbot.com',
            'role'  => 'admin',
        ]);

        $this->bodeguero = User::factory()->create([
            'email' => 'bodega_cfg@inbot.com',
            'role'  => 'bodeguero',
        ]);

        $this->cajero = User::factory()->create([
            'email' => 'cajero_cfg@inbot.com',
            'role'  => 'vendedor',
        ]);
    }

    public function test_admin_can_update_company_settings(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'nombre_comercial'      => 'FERRETERÍA LA CENTRAL',
            'razon_social'         => 'Inversiones Ferreteras S.A.S.',
            'nit'                  => '800.123.456-7',
            'telefono'             => '+57 311 000 1122',
            'correo'               => 'gerencia@lacentral.com',
            'direccion'            => 'Carrera 10 # 20-30',
            'ciudad'               => 'Medellín',
            'pais'                 => 'Colombia',
            'moneda_simbolo'       => '$',
            'moneda_codigo'        => 'COP',
            'impuesto_nombre'      => 'IVA',
            'impuesto_porcentaje'  => 19.00,
            'stock_minimo_defecto' => 10,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('company_settings', [
            'nombre_comercial' => 'FERRETERÍA LA CENTRAL',
            'nit'              => '800.123.456-7',
            'ciudad'           => 'Medellín',
        ]);
    }

    public function test_non_admin_cannot_update_company_settings(): void
    {
        // Bodeguero recibe 403
        $resBode = $this->actingAs($this->bodeguero)->put(route('admin.settings.update'), [
            'nombre_comercial'     => 'Hacked Bodega',
            'razon_social'        => 'Hacked S.A.S.',
            'nit'                 => '123',
            'moneda_simbolo'      => '$',
            'moneda_codigo'       => 'COP',
            'impuesto_nombre'     => 'IVA',
            'impuesto_porcentaje' => 19,
            'stock_minimo_defecto'=> 5,
        ]);
        $resBode->assertStatus(403);

        // Cajero recibe 403
        $resCajero = $this->actingAs($this->cajero)->put(route('admin.settings.update'), [
            'nombre_comercial'     => 'Hacked Cajero',
            'razon_social'        => 'Hacked S.A.S.',
            'nit'                 => '123',
            'moneda_simbolo'      => '$',
            'moneda_codigo'       => 'COP',
            'impuesto_nombre'     => 'IVA',
            'impuesto_porcentaje' => 19,
            'stock_minimo_defecto'=> 5,
        ]);
        $resCajero->assertStatus(403);
    }

    public function test_dashboard_renders_company_settings_tab_for_admin_only(): void
    {
        $resAdmin = $this->actingAs($this->admin)->get('/dashboard');
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Configuración Empresa');
        $resAdmin->assertSee('pane-configuracion');

        $resBode = $this->actingAs($this->bodeguero)->get('/dashboard');
        $resBode->assertStatus(200);
        $resBode->assertDontSee('pane-configuracion');

        $resCajero = $this->actingAs($this->cajero)->get('/dashboard');
        $resCajero->assertStatus(200);
        $resCajero->assertDontSee('pane-configuracion');
    }
}

