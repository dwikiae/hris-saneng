@extends('emails.recruitment.layout', ['title' => $title, 'companyName' => $companyName])

@section('content')
    <p>Yth. {{ $applicantName }},</p>
    <p>
        Selamat, Anda masuk ke tahap pemberkasan di {{ $companyName }}.
        Silakan unggah dokumen yang diminta melalui portal pemberkasan.
    </p>
    <p style="margin: 24px 0;">
        <a href="{{ $portalUrl }}" style="background: #2563eb; color: #ffffff; padding: 12px 18px; text-decoration: none; display: inline-block;">
            Buka Portal Pemberkasan
        </a>
    </p>
    <p>Tautan ini hanya berlaku sampai batas waktu yang ditentukan.</p>
@endsection
