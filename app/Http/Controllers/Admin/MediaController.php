<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Producto;
use App\Services\DebugLogService;
use App\Services\ImageHashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Store a newly uploaded media file securely.
     * Permite atribuir inmediatamente la imagen a un producto.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:5120', // 5 MB max
            ],
            'producto_id' => ['nullable', 'exists:productos,id'],
        ]);

        $file = $request->file('file');
        // Stores securely in storage/app/public/media with a random hash name
        $path = $file->store('media', 'public');

        $media = Media::create([
            'name'        => $validated['name'],
            'path'        => $path,
            'mime_type'   => $file->getMimeType(),
            'size'        => $file->getSize(),
            'producto_id' => $validated['producto_id'] ?? null,
        ]);

        // Atribuir la imagen al producto si fue seleccionado
        if (! empty($validated['producto_id'])) {
            $producto = Producto::find($validated['producto_id']);
            if ($producto) {
                $producto->imagen = $path;
                $absolutePath = Storage::disk('public')->path($path);
                $producto->image_hash = ImageHashService::generateHash($absolutePath);
                $producto->save();

                DebugLogService::log('MEDIA_ATRIBUIDO_A_PRODUCTO', [
                    'media_id'    => $media->id,
                    'producto_id' => $producto->id,
                    'producto'    => $producto->nombre,
                ]);
            }

            return redirect()
                ->route('dashboard')
                ->with('success', "Archivo cargado y atribuido exitosamente al producto '{$producto->nombre}'.");
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Archivo cargado correctamente.');
    }

    /**
     * Atribuye una imagen ya existente en la biblioteca a un producto.
     */
    public function atribuir(Request $request, Media $media): RedirectResponse
    {
        $validated = $request->validate([
            'producto_id' => ['nullable', 'exists:productos,id'],
        ]);

        if (! empty($validated['producto_id'])) {
            $producto = Producto::findOrFail($validated['producto_id']);
            $media->producto_id = $producto->id;
            $media->save();

            $producto->imagen = $media->path;
            $absolutePath = Storage::disk('public')->path($media->path);
            $producto->image_hash = ImageHashService::generateHash($absolutePath);
            $producto->save();

            DebugLogService::log('MEDIA_ATRIBUIDO_A_PRODUCTO', [
                'media_id'    => $media->id,
                'producto_id' => $producto->id,
                'producto'    => $producto->nombre,
            ]);

            return redirect()
                ->route('dashboard')
                ->with('success', "Imagen '{$media->name}' atribuida exitosamente al producto '{$producto->nombre}'.");
        } else {
            if ($media->producto_id) {
                $productoAnterior = Producto::find($media->producto_id);
                if ($productoAnterior && $productoAnterior->imagen === $media->path) {
                    $productoAnterior->imagen = null;
                    $productoAnterior->image_hash = null;
                    $productoAnterior->save();
                }
            }
            $media->producto_id = null;
            $media->save();

            return redirect()
                ->route('dashboard')
                ->with('success', "Imagen '{$media->name}' desvinculada del producto.");
        }
    }

    /**
     * Replace an existing image file and/or update its name.
     * Guideline 5.13 and Maintenance
     */
    public function update(Request $request, Media $media): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:5120',
            ],
        ]);

        if ($request->hasFile('file')) {
            // Delete old file from public disk to prevent orphaned files
            if ($media->path && Storage::disk('public')->exists($media->path)) {
                Storage::disk('public')->delete($media->path);
            }

            $newFile = $request->file('file');
            $path = $newFile->store('media', 'public');

            $media->path = $path;
            $media->mime_type = $newFile->getMimeType();
            $media->size = $newFile->getSize();
        }

        if (! empty($validated['name'])) {
            $media->name = $validated['name'];
        }

        $media->save();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Imagen actualizada correctamente.');
    }

    /**
     * Delete an existing media file and its record.
     * Reto A - Eliminar archivos
     */
    public function destroy(Media $media): RedirectResponse
    {
        // Delete physical file from storage
        if ($media->path && Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        // Si el archivo estaba atribuido a un producto, limpiar la referencia
        if ($media->producto_id) {
            $producto = Producto::find($media->producto_id);
            if ($producto && $producto->imagen === $media->path) {
                $producto->imagen = null;
                $producto->image_hash = null;
                $producto->save();
            }
        }

        $media->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Archivo eliminado correctamente.');
    }
}
