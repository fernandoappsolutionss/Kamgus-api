@component('emails.layouts.base', ['title' => 'Confirma la eliminación de tu cuenta'])
    <h2 style="margin:0 0 16px 0;font-size:22px;color:#111827;">Confirma la eliminación de tu cuenta</h2>

    <p style="margin:0 0 16px 0;">
        Hola{{ isset($name) ? ' ' . $name : '' }},
    </p>

    <p style="margin:0 0 16px 0;">
        Recibimos una solicitud para <strong>eliminar permanentemente</strong> tu cuenta en Kamgus.
        Si confirmas, perderás todo tu historial de servicios, pagos y datos asociados.
    </p>

    <div style="margin:24px 0;padding:16px;background:#fef3c7;border-left:4px solid #f59e0b;border-radius:4px;">
        <p style="margin:0;font-size:14px;color:#92400e;">
            <strong>⚠️ Acción irreversible.</strong> No podremos recuperar tus datos una vez confirmada la eliminación.
        </p>
    </div>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
        <tr>
            <td style="border-radius:8px;background:#dc2626;">
                <a href="{{ $confirmUrl ?? '#' }}"
                   style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:8px;">
                    Confirmar eliminación
                </a>
            </td>
        </tr>
    </table>

    @isset($code)
    <p style="margin:0 0 16px 0;">
        Tu código de confirmación es:
    </p>
    <p style="margin:0 0 24px 0;text-align:center;">
        <span style="display:inline-block;padding:12px 24px;background:#f3f4f6;border-radius:8px;font-family:'SF Mono',Menlo,Consolas,monospace;font-size:24px;font-weight:600;letter-spacing:4px;color:#111827;">
            {{ $code }}
        </span>
    </p>
    @endisset

    <p style="margin:24px 0 0 0;font-size:13px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:16px;">
        Si tú no solicitaste esta eliminación, ignora este correo. Tu cuenta seguirá activa.
    </p>
@endcomponent
