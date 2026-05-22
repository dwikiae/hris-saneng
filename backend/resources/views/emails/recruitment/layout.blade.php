<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5; margin: 0; padding: 24px; background: #f8fafc;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb;">
        <tr>
            <td style="padding: 24px;">
                <h1 style="font-size: 20px; margin: 0 0 16px;">{{ $title }}</h1>
                @yield('content')
                <p style="margin: 24px 0 0; color: #6b7280; font-size: 13px;">
                    Hormat kami,<br>
                    Tim HR {{ $companyName }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
