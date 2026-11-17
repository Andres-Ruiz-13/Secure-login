<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Marcar notificación individual como leída
     */
    public function markAsRead(Request $request, SystemNotification $notification): JsonResponse|RedirectResponse
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    /**
     * Marcar todas las notificaciones del rol o usuario como leídas
     */
    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        SystemNotification::paraRol($user->role)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }
}

