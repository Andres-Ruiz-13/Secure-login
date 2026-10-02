<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isConfirmationToUser ? 'Confirmación de contacto' : 'Nuevo mensaje de contacto' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#333333;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0"
       style="max-width:600px;background:#ffffff;margin:40px auto;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
    <tr>
        <td style="background:#022766;padding:30px;text-align:center;color:#ffffff;">
            <h1 style="margin:0;font-size:24px;font-weight:700;">{{ config('app.name', 'INBOT System') }}</h1>
            <p style="margin:6px 0 0 0;font-size:14px;color:#cbd5e1;">Plataforma de Gestión y CMS Seguro</p>
        </td>
    </tr>
    <tr>
        <td style="padding:35px;">
            @if($isConfirmationToUser)
                <h2 style="color:#022766;margin-top:0;">¡Hemos recibido tu mensaje!</h2>
                <p>Hola <strong>{{ $name }}</strong>, gracias por comunicarte con nosotros. Tu mensaje ha sido recibido por nuestro equipo de soporte y te responderemos a la brevedad.</p>
                <hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0;">
                <p><strong>Copia de tu mensaje:</strong></p>
            @else
                <h2 style="color:#022766;margin-top:0;">Nuevo mensaje de contacto</h2>
                <p>Se ha recibido una nueva solicitud desde el sitio web:</p>
                <hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0;">
                <p><strong>Nombre:</strong> {{ $name }}</p>
                <p><strong>Correo:</strong> {{ $email }}</p>
                @if(!empty($phone))
                    <p><strong>Teléfono:</strong> {{ $phone }}</p>
                @endif
                <p><strong>Mensaje:</strong></p>
            @endif

            <div style="background:#f8fafc;padding:20px;border-radius:8px;border-left:4px solid #022766;font-size:14px;line-height:1.6;color:#1e293b;">
                {{ $userMessage }}
            </div>
        </td>
    </tr>
    <tr>
        <td style="background:#f1f5f9;padding:20px;text-align:center;font-size:12px;color:#64748b;">
            Este mensaje fue generado automáticamente por el sistema de {{ config('app.name') }}. Por favor no responda directamente a este correo si no corresponde.
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
