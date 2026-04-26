@component('emails.layouts.base', ['title' => 'Retiro finalizado'])
    <h2 style="margin:0 0 16px 0;font-size:22px;color:#111827;">Retiro procesado ✓</h2>

    <p style="margin:0 0 16px 0;">
        Hola{{ isset($driverName) ? ' ' . $driverName : '' }},
    </p>

    <p style="margin:0 0 16px 0;">
        Tu solicitud de retiro ha sido procesada exitosamente. Aquí los detalles:
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:24px 0;border-collapse:collapse;">
        @isset($amount)
        <tr>
            <td style="padding:12px 16px;background:#f9fafb;border-radius:4px 4px 0 0;color:#6b7280;font-size:13px;width:40%;">Monto</td>
            <td style="padding:12px 16px;background:#f9fafb;border-radius:4px 4px 0 0;font-weight:600;font-size:15px;">${{ number_format($amount, 2) }}</td>
        </tr>
        @endisset
        @isset($transactionId)
        <tr>
            <td style="padding:12px 16px;color:#6b7280;font-size:13px;border-top:1px solid #e5e7eb;">ID de transacción</td>
            <td style="padding:12px 16px;font-family:'SF Mono',Menlo,Consolas,monospace;font-size:13px;border-top:1px solid #e5e7eb;">{{ $transactionId }}</td>
        </tr>
        @endisset
        @isset($bank)
        <tr>
            <td style="padding:12px 16px;color:#6b7280;font-size:13px;border-top:1px solid #e5e7eb;">Banco</td>
            <td style="padding:12px 16px;font-size:14px;border-top:1px solid #e5e7eb;">{{ $bank }}</td>
        </tr>
        @endisset
        @isset($accountNumber)
        <tr>
            <td style="padding:12px 16px;color:#6b7280;font-size:13px;border-top:1px solid #e5e7eb;border-radius:0 0 4px 4px;">Cuenta</td>
            <td style="padding:12px 16px;font-family:'SF Mono',Menlo,Consolas,monospace;font-size:13px;border-top:1px solid #e5e7eb;border-radius:0 0 4px 4px;">{{ $accountNumber }}</td>
        </tr>
        @endisset
        @isset($date)
        <tr>
            <td style="padding:12px 16px;color:#6b7280;font-size:13px;border-top:1px solid #e5e7eb;">Fecha</td>
            <td style="padding:12px 16px;font-size:14px;border-top:1px solid #e5e7eb;">{{ $date }}</td>
        </tr>
        @endisset
    </table>

    <p style="margin:0 0 16px 0;font-size:14px;color:#6b7280;">
        El monto debería reflejarse en tu cuenta bancaria en las próximas 24-72 horas hábiles según tu banco.
    </p>

    <p style="margin:24px 0 0 0;font-size:13px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:16px;">
        ¿Algún problema con tu retiro? Contáctanos respondiendo este correo o en
        <a href="mailto:info@kamgus.com" style="color:#0066cc;">info@kamgus.com</a>.
    </p>
@endcomponent
