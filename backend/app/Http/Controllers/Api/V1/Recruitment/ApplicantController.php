<?php

namespace App\Http\Controllers\Api\V1\Recruitment;

use App\Application\Recruitment\ApplicantNoteService;
use App\Application\Recruitment\ApplicantService;
use App\Application\Recruitment\BlacklistService;
use App\Application\Recruitment\StageAttachmentService;
use App\Http\Requests\Recruitment\AdvanceStageRequest;
use App\Http\Requests\Recruitment\ScheduleInterviewRequest;
use App\Http\Requests\Recruitment\StoreApplicantNoteRequest;
use App\Http\Requests\Recruitment\UploadStageAttachmentRequest;
use App\Http\Resources\Recruitment\ApplicantNoteResource;
use App\Http\Resources\Recruitment\ApplicantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ApplicantController extends RecruitmentController
{
    public function __construct(
        private readonly ApplicantService $applicants,
        private readonly BlacklistService $blacklists,
        private readonly ApplicantNoteService $notes,
        private readonly StageAttachmentService $attachments
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('recruitment.applicant.view');
        return $this->success(ApplicantResource::collection($this->applicants->index($request->query())), 'recruitment.applicant.list');
    }

    public function show(int $id): JsonResponse
    {
        Gate::authorize('recruitment.applicant.view');
        return $this->success(ApplicantResource::make($this->applicants->show($id)), 'recruitment.applicant.detail');
    }

    public function advanceToTesTulis(Request $request, int $id): JsonResponse
    {
        $this->applicants->advanceToTesTulis($id, (int) $request->user()?->getKey());
        return $this->success(null, 'recruitment.applicant.stage_advanced');
    }

    public function resendQuizLink(Request $request, int $id): JsonResponse
    {
        try {
            $this->applicants->resendQuizLink($id, (int) $request->user()?->getKey());
            return $this->success(null, 'recruitment.quiz.resent');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }

    public function scheduleInterview(ScheduleInterviewRequest $request, int $id): JsonResponse
    {
        return $this->success($this->applicants->scheduleInterview($id, $request->validated(), (int) $request->user()?->getKey()), 'recruitment.interview.scheduled', 201);
    }

    public function advanceStage(AdvanceStageRequest $request, int $id): JsonResponse
    {
        try {
            return $this->success(ApplicantResource::make($this->applicants->advanceStage($id, (string) $request->validated('to_stage'), (int) $request->user()?->getKey(), $request->validated('catatan'))), 'recruitment.applicant.stage_advanced');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        return $this->success(ApplicantResource::make($this->applicants->rejectApplicant($id, (int) $request->user()?->getKey(), $request->string('reason')->toString() ?: null)), 'recruitment.applicant.rejected');
    }

    public function restore(AdvanceStageRequest $request, int $id): JsonResponse
    {
        return $this->success(ApplicantResource::make($this->applicants->restoreFromRejected($id, (string) $request->validated('to_stage'), (int) $request->user()?->getKey())), 'recruitment.applicant.restored');
    }

    public function hire(Request $request, int $id): JsonResponse
    {
        try {
            return $this->success(ApplicantResource::make($this->applicants->hireApplicant($id, (int) $request->user()?->getKey())), 'recruitment.applicant.hired');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }

    public function resendPemberkasanLink(Request $request, int $id): JsonResponse
    {
        $this->applicants->resendPemberkasanLink($id, (int) $request->user()?->getKey());
        return $this->success(null, 'recruitment.pemberkasan.resent');
    }

    public function blacklist(Request $request, int $id): JsonResponse
    {
        return $this->success($this->blacklists->blacklist($id, (int) $request->user()?->getKey(), $request->string('alasan')->toString() ?: null), 'recruitment.applicant.blacklisted');
    }

    public function unblacklist(Request $request, int $id): JsonResponse
    {
        $this->blacklists->unblacklist($id, (int) $request->user()?->getKey());
        return $this->success(null, 'recruitment.applicant.unblacklisted');
    }

    public function addNote(StoreApplicantNoteRequest $request, int $id): JsonResponse
    {
        return $this->success(ApplicantNoteResource::make($this->notes->addNote($id, (string) $request->validated('catatan'), (int) $request->user()?->getKey())), 'recruitment.note.created', 201);
    }

    public function getNotes(Request $request, int $id): JsonResponse
    {
        return $this->success(ApplicantNoteResource::collection($this->notes->getNotes($id, (int) $request->user()?->getKey())), 'recruitment.note.list');
    }

    public function uploadAttachment(UploadStageAttachmentRequest $request, int $id, string $stage): JsonResponse
    {
        return $this->success($this->attachments->upload($id, $stage, $request->file('file'), (int) $request->user()?->getKey()), 'recruitment.attachment.uploaded', 201);
    }
}
