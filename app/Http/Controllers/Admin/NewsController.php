<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Store a newly created news article with associated media.
     * Reto B - Publicación
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'excerpt'   => ['nullable', 'string', 'max:500'],
            'content'   => ['required', 'string'],
            'media_id'  => ['nullable', 'exists:media,id'],
            'file'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'published' => ['nullable', 'boolean'],
        ]);

        $mediaId = $validated['media_id'] ?? null;

        // If user uploaded a new image directly for this news
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('media', 'public');

            $media = Media::create([
                'name'      => $validated['title'],
                'path'      => $path,
                'mime_type' => $file->getMimeType(),
                'size'      => $file->getSize(),
            ]);

            $mediaId = $media->id;
        }

        // Generate a unique slug
        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug;
        $counter = 1;
        while (News::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        News::create([
            'title'     => $validated['title'],
            'slug'      => $slug,
            'excerpt'   => $validated['excerpt'] ?? Str::limit(strip_tags($validated['content']), 140),
            'content'   => $validated['content'],
            'media_id'  => $mediaId,
            'published' => $request->boolean('published', true),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Noticia creada y publicada correctamente.');
    }

    /**
     * Update an existing news article and its media.
     * Section 5.13 - Reemplazar una imagen
     */
    public function update(Request $request, News $news): RedirectResponse
    {
        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'excerpt'   => ['nullable', 'string', 'max:500'],
            'content'   => ['required', 'string'],
            'media_id'  => ['nullable', 'exists:media,id'],
            'file'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'published' => ['nullable', 'boolean'],
        ]);

        // Section 5.13: Reemplazar una imagen
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if ($news->media) {
                // Delete previous file from storage
                if (Storage::disk('public')->exists($news->media->path)) {
                    Storage::disk('public')->delete($news->media->path);
                }

                $path = $file->store('media', 'public');
                $news->media->update([
                    'path'      => $path,
                    'mime_type' => $file->getMimeType(),
                    'size'      => $file->getSize(),
                ]);
            } else {
                $path = $file->store('media', 'public');
                $newMedia = Media::create([
                    'name'      => $validated['title'],
                    'path'      => $path,
                    'mime_type' => $file->getMimeType(),
                    'size'      => $file->getSize(),
                ]);
                $news->media_id = $newMedia->id;
            }
        } elseif ($request->has('media_id')) {
            $news->media_id = $validated['media_id'];
        }

        $news->title = $validated['title'];
        $news->excerpt = $validated['excerpt'] ?? Str::limit(strip_tags($validated['content']), 140);
        $news->content = $validated['content'];
        $news->published = $request->boolean('published');
        $news->save();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Noticia actualizada correctamente.');
    }

    /**
     * Delete an existing news article.
     */
    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Noticia eliminada correctamente.');
    }
}
