<?php

declare(strict_types=1);

namespace App\Tests\Type;

use ReflectionProperty;
use App\Entity\Maxfield;
use App\Service\MaxFieldHelper;
use App\Type\MaxfieldStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

final class MaxfieldStatusTest extends TestCase
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

        $this->assertSame(1, $status->getId());
        $this->assertSame('Test Field', $status->getName());
        $this->assertSame('test-path', $status->getPath());
        $this->assertSame('finished', $status->getStatus());
        $this->assertStringContainsString('Total maxfield runtime', $status->getLog());
        $this->assertTrue($status->isFilesFinished());
        $this->assertSame('10', $status->getFramesDirCount());
        $this->assertSame('1.5 MB', $status->getMovieSize());
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

        $this->assertSame('error', $status->getStatus());
        $this->assertFalse($status->isFilesFinished());
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

        $this->assertSame('running', $status->getStatus());
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

        $this->assertSame('X', $status->getStatus());
    }

    private function createMaxfield(int $id, string $name, string $path): Maxfield
    {
        $maxfield = new Maxfield();
        $maxfield->setName($name);
        $maxfield->setPath($path);

        $ref = new ReflectionProperty(Maxfield::class, 'id');
        $ref->setValue($maxfield, $id);

        return $maxfield;
    }
}
