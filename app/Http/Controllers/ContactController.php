<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage as ContactMessageMailable;
use App\Models\ContactMessage;
use App\Services\AuditService;
use App\Services\MailConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Procesa y almacena un mensaje de contacto, enviando las notificaciones correspondientes.
     * POST /contacto
     */
    public function send(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'string', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:200'],
        ]);

        // Registrar mensaje en la base de datos
        $contactMessage = ContactMessage::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? null,
            'subject'    => $validated['subject'] ?? null,
            'message'    => $validated['message'],
            'status'     => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]);

        // Cargar configuración de correo dinámica si existe en BD
        MailConfigurationService::applyDynamicConfiguration();

        $adminEmail = config('mail.from.address', 'administracion@empresa.com');

        try {
            // 1. Correo al administrador
            Mail::to($adminEmail)->send(new ContactMessageMailable(
                name: $validated['name'],
                email: $validated['email'],
                userMessage: $validated['message'],
                phone: $validated['phone'] ?? null,
                subjectLine: $validated['subject'] ?? 'Nuevo mensaje de contacto',
                isConfirmationToUser: false
            ));

            // 2. Correo de confirmación al usuario (PDF 1 - Sección 6.9)
            Mail::to($validated['email'])->send(new ContactMessageMailable(
                name: $validated['name'],
                email: $validated['email'],
                userMessage: $validated['message'],
                phone: $validated['phone'] ?? null,
                subjectLine: 'Hemos recibido tu mensaje',
                isConfirmationToUser: true
            ));
        } catch (\Throwable $e) {
            Log::warning('Error en el envío de correo de contacto (verifique configuración SMTP): ' . $e->getMessage());
        }

        AuditService::log('contacto_enviado', ContactMessage::class, $contactMessage->id, [
            'email'  => $contactMessage->email,
            'nombre' => $contactMessage->name,
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Tu mensaje ha sido enviado correctamente. Nos pondremos en contacto pronto.',
                'id'      => $contactMessage->id,
            ], 200);
        }

        return redirect()->back()->with('success', 'Mensaje enviado correctamente. Te hemos enviado un correo de confirmación.');
    }
}
