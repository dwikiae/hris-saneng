@extends('emails.recruitment.layout', ['title' => $title, 'companyName' => $companyName])

@section('content')
    <p>Yth. {{ $applicantName }},</p>
    <p>
        Lamaran Anda untuk posisi {{ $positionTitle }} di {{ $companyName }} sudah kami terima.
        Tim HR akan meninjau data yang Anda kirimkan dan menghubungi Anda jika masuk ke tahap berikutnya.
    </p>
@endsection
