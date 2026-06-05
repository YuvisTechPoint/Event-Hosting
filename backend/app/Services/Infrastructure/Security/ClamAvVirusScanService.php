<?php

namespace HiEvents\Services\Infrastructure\Security;

use HiEvents\Services\Infrastructure\Image\Exception\CouldNotUploadImageException;
use Illuminate\Http\UploadedFile;
use Psr\Log\LoggerInterface;

class ClamAvVirusScanService implements VirusScanServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @throws CouldNotUploadImageException
     */
    public function scan(UploadedFile $file): void
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new CouldNotUploadImageException(__('Could not upload image'));
        }

        $host = config('security.upload.clamav_host', '127.0.0.1');
        $port = config('security.upload.clamav_port', 3310);

        $socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if ($socket === false) {
            $this->logger->error('ClamAV connection failed', [
                'host' => $host,
                'port' => $port,
                'error' => $errstr,
            ]);

            throw new CouldNotUploadImageException(__('File security scan is temporarily unavailable.'));
        }

        try {
            fwrite($socket, "zINSTREAM\0");
            $handle = fopen($path, 'rb');

            if ($handle === false) {
                throw new CouldNotUploadImageException(__('Could not upload image'));
            }

            while (!feof($handle)) {
                $chunk = fread($handle, 8192);
                if ($chunk === false) {
                    break;
                }
                $size = pack('N', strlen($chunk));
                fwrite($socket, $size . $chunk);
            }

            fclose($handle);
            fwrite($socket, pack('N', 0));

            $response = trim(fread($socket, 8192) ?: '');
        } finally {
            fclose($socket);
        }

        if (!str_contains($response, 'OK') && !str_contains($response, 'stream: OK')) {
            $this->logger->warning('ClamAV detected threat in uploaded file', [
                'response' => $response,
                'filename' => $file->getClientOriginalName(),
            ]);

            throw new CouldNotUploadImageException(__('The uploaded file failed the security scan.'));
        }
    }
}
