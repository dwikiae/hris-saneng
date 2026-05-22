<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Application\Recruitment\ApplicantService;
use App\Application\Recruitment\PemberkasanService;
use App\Http\Requests\Recruitment\UploadPemberkasanRequest;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class PublicPemberkasanController extends PublicController
{
    public function __construct(
        private readonly PemberkasanService $pemberkasan,
        private readonly ApplicantService $applicants
    ) {}

    public function show(string $token): JsonResponse
    {
        try {
            return $this->success($this->pemberkasan->getPortalData($token), 'recruitment.pemberkasan.detail');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }

    public function upload(UploadPemberkasanRequest $request, string $token): JsonResponse
    {
        try {
            return $this->success($this->applicants->uploadPemberkasanDocument($token, (string) $request->validated('document_type'), $request->file('file')), 'recruitment.pemberkasan.uploaded', 201);
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }
}
