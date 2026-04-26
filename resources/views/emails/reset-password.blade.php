@component('emails.layouts.base', ['title' => 'Restablece tu contraseña'])
    <h2 style="margin:0 0 16px 0;font-size:22px;color:#111827;">Restablece tu contraseña</h2>

    <p style="margin:0 0 16px 0;">
        Hola{{ isset($name) ? ' ' . $name : '' }},
    </p>

    <p style="margin:0 0 16px 0;">
        Recibimos una solicitud para restablecer la contraseña de tu cuenta en Kamgus.
        Haz click en el siguiente botón para crear una nueva contraseña:
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
        <tr>
            <td style="border-radius:8px;background:#0066cc;">
                <a href="{{ $resetUrl ?? '#' }}"
                   style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:8px;">
                    Restablecer contraseña
                </a>
            </td>
        </tr>
    </table>

    @isset($resetUrl)
    <p style="margin:0 0 16px 0;font-size:13px;color:#6b7280;">
        O copia y pega este enlace en tu navegador:<br>
        <a href="{{ $resetUrl }}" style="color:#0066cc;word-break:break-all;">{{ $resetUrl }}</a>
    </p>
    @endisset

    @isset($code)
    <p style="margin:0 0 16px 0;">
        Tu código de verificación es:
    </p>
    <p style="margin:0 0 24px 0;text-align:center;">
        <span style="display:inline-block;padding:12px 24px;background:#f3f4f6;border-radius:8px;font-family:'SF Mono',Menlo,Consolas,monospace;font-size:24px;font-weight:600;letter-spacing:4px;color:#111827;">
            {{ $code }}
        </span>
    </p>
    @endisset

    <p style="margin:24px 0 0 0;font-size:13px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:16px;">
        Si tú no solicitaste este cambio, puedes ignorar este correo y tu contraseña seguirá siendo la misma.
        Este enlace expira en 60 minutos por seguridad.
    </p>
@endcomponent
