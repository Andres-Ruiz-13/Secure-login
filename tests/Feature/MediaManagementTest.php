<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create a genuine JPEG file binary without needing PHP GD extension.
     */
    protected function createFakeImage(string $name = 'portada.jpg', int $sizeInKb = 100): UploadedFile
    {
        $header = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
        $paddingNeeded = max(0, ($sizeInKb * 1024) - strlen($header));
        $content = $header . str_repeat("\0", $paddingNeeded);

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_home_page_is_accessible_and_renders_cleanly(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('INBOT SYSTEM');
        $response->assertSee('Gestión Inteligente de Inventarios');
    }

    public function test_guests_cannot_upload_media_or_access_dashboard(): void
    {
        $getDashboard = $this->get('/dashboard');
        $getDashboard->assertRedirect('/login');

        $postMedia = $this->post('/admin/media', [
            'name' => 'Foto de prueba',
        ]);
        $postMedia->assertRedirect('/login');
    }

    public function test_authenticated_user_can_upload_valid_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = $this->createFakeImage('portada.jpg', 1500); // 1.5MB

        $response = $this->actingAs($user)->post('/admin/media', [
            'name' => 'Imagen de Portada',
            'file' => $file,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Archivo cargado correctamente.');

        $this->assertDatabaseHas('media', [
            'name' => 'Imagen de Portada',
        ]);

        $media = Media::where('name', 'Imagen de Portada')->first();
        $this->assertNotNull($media);
        $this->assertEquals('image/jpeg', $media->mime_type);
        $this->assertGreaterThan(0, $media->size);

        Storage::disk('public')->assertExists($media->path);
    }

    public function test_image_validation_rejects_disallowed_extension_or_mime(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // Attempt to upload a text or script file
        $file = UploadedFile::fake()->create('script.php', 100, 'text/x-php');

        $response = $this->actingAs($user)->post('/admin/media', [
            'name' => 'Archivo Malicioso',
            'file' => $file,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseMissing('media', [
            'name' => 'Archivo Malicioso',
        ]);
    }

    public function test_image_validation_rejects_files_over_5mb(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // 6000 KB > 5120 KB limit
        $oversizedFile = $this->createFakeImage('pesada.jpg', 6000);

        $response = $this->actingAs($user)->post('/admin/media', [
            'name' => 'Imagen Muy Pesada',
            'file' => $oversizedFile,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseMissing('media', [
            'name' => 'Imagen Muy Pesada',
        ]);
    }

    public function test_image_replacement_deletes_old_file_and_updates_record(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // 1. Initial upload
        $file1 = $this->createFakeImage('inicial.jpg', 500);
        $this->actingAs($user)->post('/admin/media', [
            'name' => 'Foto Original',
            'file' => $file1,
        ]);

        $media = Media::where('name', 'Foto Original')->first();
        $oldPath = $media->path;
        Storage::disk('public')->assertExists($oldPath);

        // 2. Replace image (Section 5.13)
        $file2 = $this->createFakeImage('reemplazo.jpg', 800);
        $response = $this->actingAs($user)->put("/admin/media/{$media->id}", [
            'name' => 'Foto Actualizada',
            'file' => $file2,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Imagen actualizada correctamente.');

        // Old file must be deleted physically
        Storage::disk('public')->assertMissing($oldPath);

        // New file must exist
        $media->refresh();
        $this->assertEquals('Foto Actualizada', $media->name);
        $this->assertNotEquals($oldPath, $media->path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_image_deletion_removes_physical_file_and_database_record(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = $this->createFakeImage('borrar.jpg', 300);
        $this->actingAs($user)->post('/admin/media', [
            'name' => 'Imagen Para Borrar',
            'file' => $file,
        ]);

        $media = Media::where('name', 'Imagen Para Borrar')->first();
        $filePath = $media->path;
        Storage::disk('public')->assertExists($filePath);

        $response = $this->actingAs($user)->delete("/admin/media/{$media->id}");

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Archivo eliminado correctamente.');

        Storage::disk('public')->assertMissing($filePath);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_news_can_be_associated_with_media_and_displayed_on_public_home(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // 1. Upload Media
        $file = $this->createFakeImage('noticia_header.jpg', 800);
        $this->actingAs($user)->post('/admin/media', [
            'name' => 'Cabecera Noticia',
            'file' => $file,
        ]);
        $media = Media::first();

        // 2. Create News referencing existing media (avoid duplicating files)
        $response = $this->actingAs($user)->post('/admin/news', [
            'title'     => 'Gran Lanzamiento del CMS',
            'excerpt'   => 'Un breve extracto de la noticia destacada.',
            'content'   => 'Este es el contenido completo y detallado de la noticia publicada.',
            'media_id'  => $media->id,
            'published' => 1,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('news', [
            'title'    => 'Gran Lanzamiento del CMS',
            'media_id' => $media->id,
        ]);

        // 3. Visit public home page and verify display
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Gran Lanzamiento del CMS');
        $homeResponse->assertSee('Un breve extracto de la noticia destacada.');
        $homeResponse->assertSee(Storage::url($media->path));
    }
}
