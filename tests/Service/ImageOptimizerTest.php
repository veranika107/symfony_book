<?php

namespace App\Tests\Service;

use App\Service\ImageOptimizer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ImageOptimizerTest extends TestCase
{
    const RATIO_MEASUREMENT_ERROR = 0.02;

    const MAX_IMAGE_WIDTH = 200;

    const MAX_IMAGE_HEIGHT = 150;

    public function testResize(): void
    {
        $testImagesDir = dirname(__DIR__, 2) . '/tests/testimages/';
        $imageOptimizer = new ImageOptimizer();

        $initialImage = $testImagesDir . 'image-to-resize.png';
        list($initialImageWidth, $initialImageHeight) = getimagesize($initialImage);
        $initialImageRatio = round($initialImageWidth / $initialImageHeight);

        $resizedImage = $testImagesDir . 'resized-image.png';
        copy($initialImage, $resizedImage);
        $imageOptimizer->resize($resizedImage);
        list($resizedImageWidth, $resizedImageHeight) = getimagesize($resizedImage);
        $resizedImageRatio = round($resizedImageWidth / $resizedImageHeight);
        unlink($resizedImage);

        $this->assertLessThanOrEqual(self::RATIO_MEASUREMENT_ERROR, 1 - min($initialImageRatio, $resizedImageRatio) / max($initialImageRatio, $resizedImageRatio));
        $this->assertLessThanOrEqual(self::MAX_IMAGE_WIDTH, $resizedImageWidth);
        $this->assertLessThanOrEqual(self::MAX_IMAGE_HEIGHT, $resizedImageHeight);
    }
}
