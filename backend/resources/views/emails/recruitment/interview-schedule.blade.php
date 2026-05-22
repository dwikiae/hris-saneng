@extends('emails.recruitment.layout', ['title' => $title, 'companyName' => $companyName])

@section('content')
    <p>Yth. {{ $applicantName }},</p>
    <p>
        Anda dijadwalkan mengikuti interview untuk posisi {{ $positionTitle }}.
    </p>
    <p>
        Jadwal: {{ $scheduledAt }}<br>
        Lokasi/Platform: {{ $location }}<br>
        WhatsApp HRD: {{ $hrWhatsappNumber }}
    </p>
    <p>Silakan konfirmasi kehadiran Anda melalui tautan berikut:</p>
    <p style="margin: 24px 0;">
        <a href="{{ $confirmationUrl }}" style="background: #2563eb; color: #ffffff; padding: 12px 18px; text-decoration: none; display: inline-block;">
            Konfirmasi Kehadiran
        </a>
    </p>
    <p>Jika perlu reschedule, silakan hubungi HRD melalui nomor WhatsApp di atas.</p>
@endsection
