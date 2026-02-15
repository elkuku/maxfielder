<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\WayPointHelper;
use PHPUnit\Framework\TestCase;

final class WayPointHelperTest extends TestCase
{
    private WayPointHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new WayPointHelper('/tmp/test-project', 'https://intel.ingress.com/intel');
    }

    public function testCleanNameTrimsWhitespace(): void
    {
        $this->assertSame('Hello', $this->helper->cleanName('  Hello  '));
    }

    public function testCleanNameRemovesPunctuation(): void
    {
        $this->assertSame('Hello World', $this->helper->cleanName('Hello. World'));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello, World'));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello; World'));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello: World'));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello" World'));
        $this->assertSame('Hello World', $this->helper->cleanName("Hello' World"));
        $this->assertSame('Hello World', $this->helper->cleanName('Hello\\ World'));
    }

    public function testCleanNameReplacesAccents(): void
    {
        $this->assertSame('cafe', $this->helper->cleanName('café'));
        $this->assertSame('ninio', $this->helper->cleanName('niño'));
        $this->assertSame('ueber', $this->helper->cleanName('über'));
        $this->assertSame('aepfel', $this->helper->cleanName('äpfel'));
        $this->assertSame('oeffnung', $this->helper->cleanName('öffnung'));
        $this->assertSame('Unico', $this->helper->cleanName('Único'));
        $this->assertSame('Otonio', $this->helper->cleanName('Otoño'));
    }

    public function testCleanNameCombined(): void
    {
        $this->assertSame('Cafe niniio', $this->helper->cleanName('  Café, niñío.  '));
    }

    public function testGetImagePath(): void
    {
        $this->assertSame('/tmp/test-project/public/wp_images/abc123.jpg', $this->helper->getImagePath('abc123'));
    }

    public function testGetRootDir(): void
    {
        $this->assertSame('/tmp/test-project/public/wp_images', $this->helper->getRootDir());
    }

    public function testGetIntelUrl(): void
    {
        $this->assertSame('https://intel.ingress.com/intel', $this->helper->getIntelUrl());
    }
}
