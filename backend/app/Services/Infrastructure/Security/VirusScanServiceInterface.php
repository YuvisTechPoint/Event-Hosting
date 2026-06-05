<?php

namespace HiEvents\Services\Infrastructure\Security;

use HiEvents\Services\Infrastructure\Image\Exception\CouldNotUploadImageException;
use Illuminate\Http\UploadedFile;

interface VirusScanServiceInterface
{
    /**
     * @throws CouldNotUploadImageException
     */
    public function scan(UploadedFile $file): void;
}
