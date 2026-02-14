<?php

namespace App\Tests\Service;

use App\Service\WayPointHelper;
use PHPUnit\Framework\TestCase;

class WayPointHelperTest extends TestCase
{
    private WayPointHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new WayPointHelper('/tmp/test-project', 'https://intel.ingress.com/intel');
    }

    public function testCleanNameTrimsWhitespace(): void
    {
        self::assertSame('Hello', $this->helper->cleanName('  Hello  '));
    }

    public function testCleanNameRemovesPunctuation(): void
    {
        self::assertSame('Hello World', $this->helper->cleanName('Hello. World'));
        self::assertSame('Hello World', $this->helper->cleanName('Hello, World'));
        self::assertSame('Hello World', $this->helper->cleanName('Hello; World'));
        self::assertSame('Hello World', $this->helper->cleanName('Hello: World'));
        self::assertSame('Hello World', $this->helper->cleanName('Hello" World'));
        self::assertSame('Hello World', $this->helper->cleanName("Hello' World"));
        self::assertSame('Hello World', $this->helper->cleanName('Hello\\ World'));
    }

    public function testCleanNameReplacesAccents(): void
    {
        self::assertSame('cafe', $this->helper->cleanName('café'));
        self::assertSame('ninio', $this->helper->cleanName('niño'));
        self::assertSame('ueber', $this->helper->cleanName('über'));
        self::assertSame('aepfel', $this->helper->cleanName('äpfel'));
        self::assertSame('oeffnung', $this->helper->cleanName('öffnung'));
        self::assertSame('Unico', $this->helper->cleanName('Único'));
        self::assertSame('Otonio', $this->helper->cleanName('Otoño'));
    }

    public function testCleanNameCombined(): void
    {
        self::assertSame('Cafe niniio', $this->helper->cleanName('  Café, niñío.  '));
    }

    public function testGetImagePath(): void
    {
        self::assertSame(
            '/tmp/test-project/public/wp_images/abc123.jpg',
            $this->helper->getImagePath('abc123')
        );
    }

    public function testGetRootDir(): void
    {
        self::assertSame(
            '/tmp/test-project/public/wp_images',
            $this->helper->getRootDir()
        );
    }

    public function testGetIntelUrl(): void
    {
        self::assertSame(
            'https://intel.ingress.com/intel',
            $this->helper->getIntelUrl()
        );
    }
}
