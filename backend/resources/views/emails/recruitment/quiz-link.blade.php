@extends('emails.recruitment.layout', ['title' => $title, 'companyName' => $companyName])

@section('content')
    <p>Yth. {{ $applicantName }},</p>
    <p>
        Anda diundang untuk mengikuti tes tertulis untuk posisi {{ $positionTitle }}.
        Durasi pengerjaan tes adalah {{ $timerMinutes }} menit.
    </p>
    <p>Silakan buka tautan berikut untuk mulai mengerjakan tes:</p>
    <p style="margin: 24px 0;">
        <a href="{{ $quizUrl }}" style="background: #2563eb; color: #ffffff; padding: 12px 18px; text-decoration: none; display: inline-block;">
            Buka Tes Tulis
        </a>
    </p>
    <p>Pastikan koneksi internet stabil sebelum memulai. Tautan ini hanya berlaku sampai batas waktu yang ditentukan.</p>
@endsection
