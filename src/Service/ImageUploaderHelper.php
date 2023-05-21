<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploaderHelper
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%photo_dir%')] private string $photoDir
    ) {
    }

    public function movePhotoToPermanentDir(UploadedFile $photo): string
    {
        $photoFilename = bin2hex(random_bytes(6)) . '.' . $photo->guessExtension();
        try {
           $photo->move($this->photoDir, $photoFilename);
        } catch (FileException $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        return $photoFilename;
    }

    public function deletePhoto(string $filename): void
    {
        $filesystem = new Filesystem();
        try {
            $filesystem->remove($this->photoDir . $filename);
        } catch (IOException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
