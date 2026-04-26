@component('emails.layouts.base', ['title' => 'Vehículo registrado'])
    <h2 style="margin:0 0 16px 0;font-size:22px;color:#111827;">¡Tu primer vehículo está registrado! 🚛</h2>

    <p style="margin:0 0 16px 0;">
        Hola{{ isset($driverName) ? ' ' . $driverName : '' }},
    </p>

    <p style="margin:0 0 16px 0;">
        Recibimos los datos de tu vehículo y ya está cargado en tu perfil de Kamgus.
        Nuestro equipo lo revisará en las próximas horas y te avisaremos cuando esté
        aprobado para empezar a recibir servicios.
    </p>

    @isset($vehicleUrl)
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
        <tr>
            <td style="border-radius:8px;background:#0066cc;">
                <a href="{{ $vehicleUrl }}"
                   style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:8px;">
                    Ver mi vehículo
                </a>
            </td>
        </tr>
    </table>
    @endisset

    <h3 style="margin:24px 0 12px 0;font-size:16px;color:#111827;">Próximos pasos</h3>

    <ol style="margin:0 0 16px 0;padding-left:20px;color:#374151;">
        <li style="margin-bottom:8px;">Mantén tus documentos al día (cédula, licencia, póliza)</li>
        <li style="margin-bottom:8px;">Revisa que tu información de cuenta bancaria esté correcta para recibir pagos</li>
        <li style="margin-bottom:8px;">Activa las notificaciones en tu app para enterarte de nuevos servicios</li>
    </ol>

    <p style="margin:24px 0 0 0;font-size:13px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:16px;">
        ¿Necesitas ayuda? Escríbenos a
        <a href="mailto:info@kamgus.com" style="color:#0066cc;">info@kamgus.com</a>.
    </p>
@endcomponent
