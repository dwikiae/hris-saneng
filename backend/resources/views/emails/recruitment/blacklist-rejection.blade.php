@extends('emails.recruitment.layout', ['title' => $title, 'companyName' => $companyName])

@section('content')
    <p>Yth. {{ $applicantName }},</p>
    <p>
        Terima kasih atas lamaran Anda kepada {{ $companyName }}. Setelah proses peninjauan,
        kami belum dapat melanjutkan lamaran Anda ke tahap berikutnya.
    </p>
    <p>
        Kami menghargai waktu dan minat Anda terhadap perusahaan kami.
    </p>
@endsection
