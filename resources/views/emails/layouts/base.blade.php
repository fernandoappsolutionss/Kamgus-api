<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Kamgus' }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;line-height:1.5;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f7fa;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                    {{-- Header --}}
                    <tr>
                        <td style="background:#0066cc;padding:24px 32px;color:#ffffff;text-align:center;">
                            <h1 style="margin:0;font-size:24px;font-weight:600;letter-spacing:-0.02em;">Kamgus</h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            {!! $slot !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;text-align:center;">
                            <p style="margin:0 0 8px 0;">
                                <strong>Kamgus</strong> · Tu solución de transporte y mudanza
                            </p>
                            <p style="margin:0;">
                                Si tienes dudas, escríbenos a
                                <a href="mailto:info@kamgus.com" style="color:#0066cc;text-decoration:none;">info@kamgus.com</a>
                            </p>
                            <p style="margin:12px 0 0 0;font-size:11px;color:#9ca3af;">
                                © {{ date('Y') }} Kamgus. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
