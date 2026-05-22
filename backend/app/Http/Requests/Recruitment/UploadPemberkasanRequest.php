<?php

namespace App\Http\Requests\Recruitment;

class UploadPemberkasanRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'in:ktp,kk,npwp,rekening,bpjs_kesehatan,bpjs_ketenagakerjaan'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
