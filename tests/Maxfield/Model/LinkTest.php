<?php

declare(strict_types=1);

namespace App\Tests\Maxfield\Model;

use Elkuku\MaxfieldBundle\Model\Link;
use PHPUnit\Framework\TestCase;

final class LinkTest extends TestCase
{
    public function testPropertyAssignment(): void
    {
        $link = new Link(2, 5, 3, true);

        $this->assertSame(2, $link->origin);
        $this->assertSame(5, $link->destination);
        $this->assertSame(3, $link->order);
        $this->assertTrue($link->reversible);
        $this->assertSame([], $link->fields);
        $this->assertSame([], $link->depends);
    }

    public function testDefaultsNotReversible(): void
    {
        $link = new Link(0, 1, 0);
        $this->assertFalse($link->reversible);
    }

    public function testKey(): void
    {
        $link = new Link(3, 7, 0);
        $this->assertSame('3,7', $link->key());
    }

    public function testAsPair(): void
    {
        $link = new Link(4, 9, 1);
        $this->assertSame([4, 9], $link->asPair());
    }
}
