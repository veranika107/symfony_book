<?php

namespace App\Tests\Service;

use App\Service\ImageOptimizer;
use PHPUnit\Framework\TestCase;

class ImageOptimizerTest extends TestCase
{
    private const RATIO_MEASUREMENT_ERROR = 0.02;

    private const MAX_IMAGE_WIDTH = 200;

    private const MAX_IMAGE_HEIGHT = 150;

    private function provideImage(): iterable
    {
        yield 'vertical image' => ['vertical'];
        yield 'horizontal image' => ['horizontal'];
    }

    /**
     * @dataProvider provideImage
     */
    public function testResize(string $imageRotation): void
    {
        $imageOptimizer = new ImageOptimizer();

        $testImagesDir = dirname(__DIR__, 2) . '/tests/asserts/';

        $initialImage = $testImagesDir . 'resize-image-' . $imageRotation . '.png';
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
