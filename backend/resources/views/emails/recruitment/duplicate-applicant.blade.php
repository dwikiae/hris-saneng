@extends('emails.recruitment.layout', ['title' => $title, 'companyName' => $companyName])

@section('content')
    <p>Yth. {{ $applicantName }},</p>
    <p>
        Terima kasih atas minat Anda kepada {{ $companyName }}. Saat ini sistem kami mencatat
        bahwa Anda sudah pernah mengirimkan lamaran sebelumnya, sehingga lamaran terbaru belum
        dapat kami proses.
    </p>
    <p>
        Anda dapat mencoba kembali setelah masa retensi data kandidat selesai, yaitu {{ $retentionDays }} hari
        sejak tanggal lamaran sebelumnya.
    </p>
@endsection
