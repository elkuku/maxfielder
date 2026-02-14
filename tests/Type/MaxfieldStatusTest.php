<?php

namespace App\Tests\Type;

use App\Entity\Maxfield;
use App\Service\MaxFieldHelper;
use App\Type\MaxfieldStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class MaxfieldStatusTest extends TestCase
{
    public function testFromMaxfieldFinished(): void
    {
        $helper = $this->createStub(MaxFieldHelper::class);
        $helper->method('getLog')->willReturn('some output Total maxfield runtime 42s');
        $helper->method('filesFinished')->willReturn(true);
        $helper->method('framesDirCount')->willReturn('10');
        $helper->method('getMovieSize')->willReturn('1.5 MB');

        $maxfield = $this->createMaxfield(1, 'Test Field', 'test-path');

        $status = (new MaxfieldStatus($helper))->fromMaxfield($maxfield);

        self::assertSame(1, $status->getId());
        self::assertSame('Test Field', $status->getName());
        self::assertSame('test-path', $status->getPath());
        self::assertSame('finished', $status->getStatus());
        self::assertStringContainsString('Total maxfield runtime', $status->getLog());
        self::assertTrue($status->isFilesFinished());
        self::assertSame('10', $status->getFramesDirCount());
        self::assertSame('1.5 MB', $status->getMovieSize());
    }

    public function testFromMaxfieldError(): void
    {
        $helper = $this->createStub(MaxFieldHelper::class);
        $helper->method('getLog')->willReturn('Traceback (most recent call last): something broke');
        $helper->method('filesFinished')->willReturn(false);
        $helper->method('framesDirCount')->willReturn('n/a');
        $helper->method('getMovieSize')->willReturn('n/a');

        $maxfield = $this->createMaxfield(2, 'Error Field', 'error-path');

        $status = (new MaxfieldStatus($helper))->fromMaxfield($maxfield);

        self::assertSame('error', $status->getStatus());
        self::assertFalse($status->isFilesFinished());
    }

    public function testFromMaxfieldRunning(): void
    {
        $helper = $this->createStub(MaxFieldHelper::class);
        $helper->method('getLog')->willReturn('Processing waypoints...');
        $helper->method('filesFinished')->willReturn(false);
        $helper->method('framesDirCount')->willReturn('5');
        $helper->method('getMovieSize')->willReturn('n/a');

        $maxfield = $this->createMaxfield(3, 'Running Field', 'running-path');

        $status = (new MaxfieldStatus($helper))->fromMaxfield($maxfield);

        self::assertSame('running', $status->getStatus());
    }

    public function testFromMaxfieldMissingLog(): void
    {
        $helper = $this->createStub(MaxFieldHelper::class);
        $helper->method('getLog')->willThrowException(new FileNotFoundException());
        $helper->method('filesFinished')->willReturn(false);
        $helper->method('framesDirCount')->willReturn('n/a');
        $helper->method('getMovieSize')->willReturn('n/a');

        $maxfield = $this->createMaxfield(4, 'Missing Field', 'missing-path');

        $status = (new MaxfieldStatus($helper))->fromMaxfield($maxfield);

        self::assertSame('X', $status->getStatus());
    }

    private function createMaxfield(int $id, string $name, string $path): Maxfield
    {
        $maxfield = new Maxfield();
        $maxfield->setName($name);
        $maxfield->setPath($path);

        $ref = new \ReflectionProperty(Maxfield::class, 'id');
        $ref->setValue($maxfield, $id);

        return $maxfield;
    }
}
