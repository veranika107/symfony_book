<?php

namespace App\Tests\Service;

use App\Service\ImageUploaderHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploaderHelperTest extends TestCase
{
    private ImageUploaderHelper $imageUploaderHelper;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $photo_dir = dirname(__DIR__, 2) . '/public/uploads/photos';
        $this->imageUploaderHelper = new ImageUploaderHelper($this->logger, $photo_dir);
    }

    public function testMovePhotoToPermanentDir(): string
    {
        // Prepare the image for moving.
        $upload_dir = dirname(__DIR__, 2) . '/public/uploads/photos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $photoPath = $upload_dir . 'test_image.gif';
        copy(dirname(__DIR__, 2) . '/public/images/under-construction.gif', $photoPath);

        $file = new UploadedFile(path: $photoPath, originalName: 'test_image.gif', mimeType: 'image/gif', test: true);

        $photoFilename = $this->imageUploaderHelper->movePhotoToPermanentDir($file);
        $photoNameWithoutExtension = basename($photoFilename, '.gif');

        $this->assertTrue(file_exists($upload_dir . $photoFilename));
        $this->assertTrue(ctype_xdigit($photoNameWithoutExtension));

        return $photoFilename;
    }

    public function testMovePhotoToPermanentDirWithError(): void
    {
        $photoPath = dirname(__DIR__, 2) . '/public/images/under-construction.gif';
        $file = new UploadedFile(path: $photoPath, originalName: 'under-construction.gif', mimeType: 'image/gif', error: \UPLOAD_ERR_NO_FILE, test: true);

        $this->logger->expects($this->once())
            ->method('error');
        $this->expectException(FileException::class);

        $this->imageUploaderHelper->movePhotoToPermanentDir($file);
    }

    /**
     * @depends testMovePhotoToPermanentDir
     */
    public function testDeletePhoto(string $photoFilename): void
    {
        $this->imageUploaderHelper->deletePhoto($photoFilename);

        $photoPath = dirname(__DIR__, 2) . '/public/uploads/photos/test_image.gif';
        $this->assertFalse(file_exists($photoPath));
    }
}
