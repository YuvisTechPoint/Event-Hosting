<?php

namespace HiEvents\Services\Infrastructure\Jobs;

use HiEvents\Services\Infrastructure\Jobs\DTO\JobPollingResultDTO;
use HiEvents\Services\Infrastructure\Jobs\Enum\JobStatusEnum;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class JobPollingService
{
    public function __construct(
        private readonly Repository $config,
    ) {
    }

    public function startJob(string $jobName, array $jobs): JobPollingResultDTO
    {
        $batch = Bus::batch($jobs)
            ->name($jobName)
            ->dispatch();

        return new JobPollingResultDTO(
            status: JobStatusEnum::IN_PROGRESS,
            message: 'Job started successfully',
            jobUuid: $batch->id,
        );
    }

    public function checkJobStatus(string $jobUuid, ?string $filePath = null, ?string $localDownloadUrl = null): JobPollingResultDTO
    {
        $batch = Bus::findBatch($jobUuid);

        if (!$batch) {
            return new JobPollingResultDTO(
                status: JobStatusEnum::NOT_FOUND,
                message: __('Job not found'),
                jobUuid: $jobUuid,
            );
        }

        if ($batch->finished()) {
            $diskName = $this->config->get('filesystems.private');
            $disk = Storage::disk($diskName);

            if ($filePath && !$disk->exists($filePath)) {
                return new JobPollingResultDTO(
                    status: JobStatusEnum::NOT_FOUND,
                    message: __('Export file not found'),
                    jobUuid: $jobUuid,
                );
            }

            return new JobPollingResultDTO(
                status: JobStatusEnum::FINISHED,
                message: __('Job completed successfully'),
                jobUuid: $jobUuid,
                downloadUrl: $filePath ? $this->getDownloadUrl($diskName, $filePath, $localDownloadUrl) : null,
            );
        }

        return new JobPollingResultDTO(
            status: JobStatusEnum::IN_PROGRESS,
            message: __('Job is still in progress'),
            jobUuid: $jobUuid,
        );
    }

    private function getDownloadUrl(string $diskName, string $filePath, ?string $localDownloadUrl): ?string
    {
        $driver = $this->config->get("filesystems.disks.{$diskName}.driver");

        if ($driver === 's3') {
            return Storage::disk($diskName)->temporaryUrl($filePath, now()->addMinutes(10));
        }

        return $localDownloadUrl;
    }
}
