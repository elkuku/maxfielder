<?php

namespace App\Tests\Twig;

use App\Entity\Maxfield;
use App\Entity\Waypoint;
use App\Service\MaxFieldHelper;
use App\Service\WayPointHelper;
use App\Twig\AppExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class AppExtensionTest extends TestCase
{
    private WayPointHelper&MockObject $wayPointHelper;
    private MaxFieldHelper&MockObject $maxFieldHelper;
    private AppExtension $extension;

    protected function setUp(): void
    {
        $this->wayPointHelper = $this->createMock(WayPointHelper::class);
        $this->maxFieldHelper = $this->createMock(MaxFieldHelper::class);
        $this->extension = new AppExtension($this->wayPointHelper, $this->maxFieldHelper);
    }

    public function testGetFiltersReturnsEmptyArray(): void
    {
        self::assertSame([], $this->extension->getFilters());
    }

    public function testGetFunctionsReturnsThreeFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        self::assertCount(3, $functions);

        $names = array_map(
            static fn(TwigFunction $f) => $f->getName(),
            $functions
        );

        self::assertContains('hasImage', $names);
        self::assertContains('previewImage', $names);
        self::assertContains('waypointCount', $names);
    }

    public function testPreviewImageReturnsImagePath(): void
    {
        $maxfield = new Maxfield();
        $maxfield->setPath('test-field');

        $this->maxFieldHelper->method('getPreviewImage')
            ->with('test-field')
            ->willReturn('maxfields/test-field/link_map.png');

        self::assertSame('maxfields/test-field/link_map.png', $this->extension->previewImage($maxfield));
    }

    public function testPreviewImageReturnsFallbackOnEmpty(): void
    {
        $maxfield = new Maxfield();
        $maxfield->setPath('missing');

        $this->maxFieldHelper->method('getPreviewImage')
            ->with('missing')
            ->willReturn('');

        self::assertSame('images/no-preview.jpg', $this->extension->previewImage($maxfield));
    }

    public function testWaypointCount(): void
    {
        $maxfield = new Maxfield();
        $maxfield->setPath('my-field');

        $this->maxFieldHelper->method('getWaypointCount')
            ->with('my-field')
            ->willReturn(42);

        self::assertSame(42, $this->extension->waypointCount($maxfield));
    }

    public function testHasImageTrue(): void
    {
        $waypoint = new Waypoint();
        $waypoint->setGuid('abc123');

        $this->wayPointHelper->method('findImage')
            ->with('abc123')
            ->willReturn('/path/to/image.jpg');

        self::assertTrue($this->extension->hasImage($waypoint));
    }

    public function testHasImageFalse(): void
    {
        $waypoint = new Waypoint();
        $waypoint->setGuid('xyz789');

        $this->wayPointHelper->method('findImage')
            ->with('xyz789')
            ->willReturn(false);

        self::assertFalse($this->extension->hasImage($waypoint));
    }
}
