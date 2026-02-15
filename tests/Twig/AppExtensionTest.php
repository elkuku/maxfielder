<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\Maxfield;
use App\Entity\Waypoint;
use App\Service\MaxFieldHelper;
use App\Service\WayPointHelper;
use App\Twig\AppExtension;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

final class AppExtensionTest extends TestCase
{
    private WayPointHelper&Stub $wayPointHelper;

    private MaxFieldHelper&Stub $maxFieldHelper;

    private AppExtension $extension;

    protected function setUp(): void
    {
        $this->wayPointHelper = $this->createStub(WayPointHelper::class);
        $this->maxFieldHelper = $this->createStub(MaxFieldHelper::class);
        $this->extension = new AppExtension($this->wayPointHelper, $this->maxFieldHelper);
    }

    public function testGetFiltersReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->extension->getFilters());
    }

    public function testGetFunctionsReturnsThreeFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(3, $functions);

        $names = array_map(
            static fn(TwigFunction $f): string => $f->getName(),
            $functions
        );

        $this->assertContains('hasImage', $names);
        $this->assertContains('previewImage', $names);
        $this->assertContains('waypointCount', $names);
    }

    public function testPreviewImageReturnsImagePath(): void
    {
        $maxfield = new Maxfield();
        $maxfield->setPath('test-field');

        $this->maxFieldHelper->method('getPreviewImage')
            ->willReturn('maxfields/test-field/link_map.png');

        $this->assertSame('maxfields/test-field/link_map.png', $this->extension->previewImage($maxfield));
    }

    public function testPreviewImageReturnsFallbackOnEmpty(): void
    {
        $maxfield = new Maxfield();
        $maxfield->setPath('missing');

        $this->maxFieldHelper->method('getPreviewImage')
            ->willReturn('');

        $this->assertSame('images/no-preview.jpg', $this->extension->previewImage($maxfield));
    }

    public function testWaypointCount(): void
    {
        $maxfield = new Maxfield();
        $maxfield->setPath('my-field');

        $this->maxFieldHelper->method('getWaypointCount')
            ->willReturn(42);

        $this->assertSame(42, $this->extension->waypointCount($maxfield));
    }

    public function testHasImageTrue(): void
    {
        $waypoint = new Waypoint();
        $waypoint->setGuid('abc123');

        $this->wayPointHelper->method('findImage')
            ->willReturn('/path/to/image.jpg');

        $this->assertTrue($this->extension->hasImage($waypoint));
    }

    public function testHasImageFalse(): void
    {
        $waypoint = new Waypoint();
        $waypoint->setGuid('xyz789');

        $this->wayPointHelper->method('findImage')
            ->willReturn(false);

        $this->assertFalse($this->extension->hasImage($waypoint));
    }
}
