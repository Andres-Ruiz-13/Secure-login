<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\NewsRead;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsNewsAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cajero;
    protected User $bodeguero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin_news@inbot.com',
            'role'  => 'admin',
        ]);

        $this->cajero = User::factory()->create([
            'email' => 'cajero_news@inbot.com',
            'role'  => 'vendedor',
        ]);

        $this->bodeguero = User::factory()->create([
            'email' => 'bodega_news@inbot.com',
            'role'  => 'bodeguero',
        ]);
    }

    public function test_user_can_mark_news_as_read(): void
    {
        $news = News::create([
            'title'       => 'Capacitación en Punto de Venta',
            'slug'        => 'capacitacion-pos',
            'content'     => 'Capacitación obligatoria para el equipo de cajeros el viernes.',
            'category'    => 'comunicado',
            'priority'    => 'alta',
            'target_role' => 'vendedor',
            'published'   => true,
            'author_id'   => $this->admin->id,
        ]);

        $response = $this->actingAs($this->cajero)->post(route('admin.news.read', $news->id));
        $response->assertRedirect();

        $this->assertDatabaseHas('news_reads', [
            'news_id' => $news->id,
            'user_id' => $this->cajero->id,
        ]);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $notif = SystemNotification::create([
            'titulo'      => 'Stock Bajo Detectado',
            'mensaje'     => 'El producto Taladro Percutor ha alcanzado su stock mínimo.',
            'tipo'        => 'warning',
            'target_role' => 'bodeguero',
            'is_read'     => false,
        ]);

        $response = $this->actingAs($this->bodeguero)->post(route('notifications.read', $notif->id));
        $response->assertRedirect();

        $notif->refresh();
        $this->assertTrue($notif->is_read);
        $this->assertNotNull($notif->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        SystemNotification::create([
            'titulo'      => 'Alerta 1',
            'mensaje'     => 'Mensaje 1',
            'target_role' => 'todos',
            'is_read'     => false,
        ]);

        SystemNotification::create([
            'titulo'      => 'Alerta 2',
            'mensaje'     => 'Mensaje 2',
            'target_role' => 'admin',
            'is_read'     => false,
        ]);

        $this->assertEquals(2, SystemNotification::where('is_read', false)->count());

        $response = $this->actingAs($this->admin)->post(route('notifications.mark-all-read'));
        $response->assertRedirect();

        $this->assertEquals(0, SystemNotification::where('is_read', false)->count());
    }

    public function test_news_query_respects_target_role(): void
    {
        News::create([
            'title'       => 'Exclusivo Bodega',
            'slug'        => 'exclusivo-bodega',
            'content'     => 'Información para inventario físico.',
            'target_role' => 'bodeguero',
            'published'   => true,
        ]);

        News::create([
            'title'       => 'General Para Todos',
            'slug'        => 'general-todos',
            'content'     => 'Horarios fin de año.',
            'target_role' => 'todos',
            'published'   => true,
        ]);

        $resCajero = $this->actingAs($this->cajero)->get('/dashboard');
        $resCajero->assertStatus(200);
        $resCajero->assertSee('General Para Todos');
        $resCajero->assertDontSee('Exclusivo Bodega');

        $resBode = $this->actingAs($this->bodeguero)->get('/dashboard');
        $resBode->assertStatus(200);
        $resBode->assertSee('Exclusivo Bodega');
        $resBode->assertSee('General Para Todos');
    }
}

