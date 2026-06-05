<?php

namespace HiEvents\Services\Infrastructure\Security;

use Illuminate\Http\UploadedFile;

class NoOpVirusScanService implements VirusScanServiceInterface
{
    public function scan(UploadedFile $file): void
    {
    }
}
