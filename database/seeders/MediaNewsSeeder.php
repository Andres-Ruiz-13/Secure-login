<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaNewsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure directory exists in public disk
        Storage::disk('public')->makeDirectory('media');

        // Sample valid SVG/JPEG data for demonstration
        $samplePath = 'media/convocatoria_2026.jpg';

        if (! Storage::disk('public')->exists($samplePath)) {
            // Write a valid JPEG header + bytes
            $header = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
            $dummyContent = $header . str_repeat("\x80", 204800); // ~200 KB
            Storage::disk('public')->put($samplePath, $dummyContent);
        }

        $media = Media::firstOrCreate(
            ['name' => 'Convocatoria Proyectos de Seguridad 2026'],
            [
                'path'      => $samplePath,
                'mime_type' => 'image/jpeg',
                'size'      => Storage::disk('public')->size($samplePath),
                'active'    => true,
            ]
        );

        News::firstOrCreate(
            ['slug' => 'apertura-convocatoria-seguridad-2026'],
            [
                'title'     => 'Apertura de la Convocatoria en Seguridad y Gestión Multimedia 2026',
                'excerpt'   => 'Se abre la convocatoria para proyectos de desarrollo seguro bajo el marco MEDIT. Conoce los requisitos y cómo participar.',
                'content'   => "Estimada comunidad académica,\n\nHoy damos apertura formal a los proyectos de gestión segura de archivos multimedia y sistemas de notificación. Todos los módulos deben validar extensiones, tipos MIME y pesos en el servidor para proteger la infraestructura contra accesos no autorizados.\n\n¡Participa ahora!",
                'media_id'  => $media->id,
                'published' => true,
            ]
        );
    }
}
