<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Store a newly uploaded media file securely.
     * Guidelines 5.6 and 5.7
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
        ]);

        $file = $request->file('file');
        // Stores securely in storage/app/public/media with a random hash name
        $path = $file->store('media', 'public');

        Media::create([
            'name'      => $validated['name'],
            'path'      => $path,
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Archivo cargado correctamente.');
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

        $media->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Archivo eliminado correctamente.');
    }
}
