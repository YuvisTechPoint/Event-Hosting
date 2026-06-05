<?php

namespace HiEvents\Http\Actions\Questions;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Jobs\Question\ExportAnswersJob;
use HiEvents\Services\Infrastructure\Jobs\JobPollingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ExportQuestionAnswersAction extends BaseAction
{
    public function __construct(private JobPollingService $jobPollingService)
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(Request $request, int $eventId): JsonResponse|BinaryFileResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        if ($jobUuid = $request->get('job_uuid')) {
            return $this->handleExistingJob($request, $jobUuid, $eventId);
        }

        return $this->startNewExportJob($eventId);
    }

    private function handleExistingJob(Request $request, string $jobUuid, int $eventId): JsonResponse|BinaryFileResponse
    {
        $filePath = "event_$eventId/answers-$jobUuid.xlsx";
        $localDownloadUrl = $request->fullUrlWithQuery([
            'job_uuid' => $jobUuid,
            'download' => 1,
        ]);

        $jobStatus = $this->jobPollingService->checkJobStatus($jobUuid, $filePath, $localDownloadUrl);

        if ($request->boolean('download') && $jobStatus->status->name === 'FINISHED' && $jobStatus->downloadUrl) {
            $diskName = config('filesystems.private');
            $disk = Storage::disk($diskName);

            if ($disk->exists($filePath)) {
                return response()->download($disk->path($filePath), "answers-$jobUuid.xlsx");
            }
        }

        return $this->jsonResponse([
            'message' => $jobStatus->message,
            'status' => $jobStatus->status->name,
            'job_uuid' => $jobStatus->jobUuid,
            'download_url' => $jobStatus->downloadUrl,
        ]);
    }

    private function startNewExportJob(int $eventId): JsonResponse
    {
        $jobStatus = $this->jobPollingService->startJob(
            jobName: "Export Questions for Event #$eventId",
            jobs: [new ExportAnswersJob($eventId)]
        );

        return $this->jsonResponse([
            'message' => $jobStatus->message,
            'status' => $jobStatus->status->name,
            'job_uuid' => $jobStatus->jobUuid,
        ]);
    }
}
