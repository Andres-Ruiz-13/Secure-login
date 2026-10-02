<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * Store a new car wash service.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:150'],
            'description'      => ['required', 'string'],
            'price'            => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'icon'             => ['nullable', 'string', 'max:50'],
            'media_id'         => ['nullable', 'exists:media,id'],
            'file'             => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $mediaId = $validated['media_id'] ?? null;

        // If direct file upload provided
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('media', 'public');

            $media = Media::create([
                'name'      => "Servicio: " . $validated['name'],
                'path'      => $path,
                'mime_type' => $file->getMimeType(),
                'size'      => $file->getSize(),
            ]);

            $mediaId = $media->id;
        }

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Service::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        Service::create([
            'name'             => $validated['name'],
            'slug'             => $slug,
            'description'      => $validated['description'],
            'price'            => $validated['price'],
            'duration_minutes' => $validated['duration_minutes'],
            'icon'             => $validated['icon'] ?? 'bi-droplet-half',
            'media_id'         => $mediaId,
            'active'           => true,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Servicio de lavado agregado correctamente.');
    }

    /**
     * Update an existing service.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:150'],
            'description'      => ['required', 'string'],
            'price'            => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'icon'             => ['nullable', 'string', 'max:50'],
            'media_id'         => ['nullable', 'exists:media,id'],
            'file'             => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('media', 'public');

            $media = Media::create([
                'name'      => "Servicio: " . $validated['name'],
                'path'      => $path,
                'mime_type' => $file->getMimeType(),
                'size'      => $file->getSize(),
            ]);

            $service->media_id = $media->id;
        } elseif ($request->has('media_id')) {
            $service->media_id = $validated['media_id'];
        }

        $service->name = $validated['name'];
        $service->description = $validated['description'];
        $service->price = $validated['price'];
        $service->duration_minutes = $validated['duration_minutes'];
        if (! empty($validated['icon'])) {
            $service->icon = $validated['icon'];
        }
        $service->save();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Servicio actualizado correctamente.');
    }

    /**
     * Delete a service.
     */
    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Servicio eliminado correctamente.');
    }
}
