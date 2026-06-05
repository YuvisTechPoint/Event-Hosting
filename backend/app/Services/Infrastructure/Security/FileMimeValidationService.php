<?php

namespace HiEvents\Services\Infrastructure\Security;

use HiEvents\Services\Infrastructure\Image\Exception\CouldNotUploadImageException;
use Illuminate\Http\UploadedFile;

class FileMimeValidationService
{
    /**
     * @throws CouldNotUploadImageException
     */
    public function assertAllowedImageMime(UploadedFile $file): void
    {
        $allowedMimes = config('security.upload.allowed_image_mimes', []);

        $detectedMime = $this->detectMimeType($file);

        if (!in_array($detectedMime, $allowedMimes, true)) {
            throw new CouldNotUploadImageException(
                __('The uploaded file type is not allowed.')
            );
        }

        $clientMime = $file->getMimeType();
        if ($clientMime && !in_array($clientMime, $allowedMimes, true)) {
            throw new CouldNotUploadImageException(
                __('The uploaded file type is not allowed.')
            );
        }
    }

    /**
     * @throws CouldNotUploadImageException
     */
    public function assertWithinSizeLimit(UploadedFile $file): void
    {
        $maxBytes = config('security.upload.max_size_kb', 8192) * 1024;

        if ($file->getSize() > $maxBytes) {
            throw new CouldNotUploadImageException(
                __('The uploaded file exceeds the maximum allowed size.')
            );
        }
    }

    private function detectMimeType(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return is_string($mime) ? $mime : '';
    }
}
