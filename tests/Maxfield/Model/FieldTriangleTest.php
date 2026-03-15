<?php

declare(strict_types=1);

namespace App\Tests\Maxfield\Model;

use Elkuku\MaxfieldBundle\Model\FieldTriangle;
use Elkuku\MaxfieldBundle\Model\Graph;
use PHPUnit\Framework\TestCase;

final class FieldTriangleTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $fld = new FieldTriangle([0, 1, 2]);

        $this->assertSame([0, 1, 2], $fld->vertices);
        $this->assertFalse($fld->exterior);
        $this->assertSame([], $fld->children);
        $this->assertSame([], $fld->contents);
        $this->assertNull($fld->splitter);
    }

    public function testExteriorFlag(): void
    {
        $fld = new FieldTriangle([0, 1, 2], exterior: true);
        $this->assertTrue($fld->exterior);
    }

    public function testGetContentsIdentifiesInteriorPortal(): void
    {
        // Triangle with vertices at (0,0), (2,0), (1,2)
        // Portal at (1, 0.5) is inside the triangle
        $portalsGno = [
            [0.0, 0.0],  // vertex 0
            [2.0, 0.0],  // vertex 1
            [1.0, 2.0],  // vertex 2
            [1.0, 0.5],  // portal 3 — interior
            [5.0, 5.0],  // portal 4 — exterior
        ];

        $fld = new FieldTriangle([0, 1, 2]);
        $fld->getContents($portalsGno);

        $this->assertContains(3, $fld->contents);
        $this->assertNotContains(4, $fld->contents);
    }

    public function testGetContentsExcludesVertices(): void
    {
        $portalsGno = [
            [0.0, 0.0],
            [2.0, 0.0],
            [1.0, 2.0],
        ];

        $fld = new FieldTriangle([0, 1, 2]);
        $fld->getContents($portalsGno);

        $this->assertNotContains(0, $fld->contents);
        $this->assertNotContains(1, $fld->contents);
        $this->assertNotContains(2, $fld->contents);
    }

    public function testGetContentsEmptyWhenNoInteriorPortals(): void
    {
        $portalsGno = [
            [0.0, 0.0],
            [2.0, 0.0],
            [1.0, 2.0],
        ];

        $fld = new FieldTriangle([0, 1, 2]);
        $fld->getContents($portalsGno);

        $this->assertSame([], $fld->contents);
    }

    public function testSplitCreatesThreeChildren(): void
    {
        $portalsGno = [
            [0.0, 0.0],  // vertex 0
            [2.0, 0.0],  // vertex 1
            [1.0, 2.0],  // vertex 2
            [1.0, 0.5],  // interior splitter
        ];

        $fld = new FieldTriangle([0, 1, 2]);
        $fld->getContents($portalsGno);
        $fld->split();

        $this->assertCount(3, $fld->children);
        $this->assertNotNull($fld->splitter);
    }

    public function testSplitDoesNothingWhenNoContents(): void
    {
        $fld = new FieldTriangle([0, 1, 2]);
        // contents is empty
        $fld->split();

        $this->assertSame([], $fld->children);
        $this->assertNull($fld->splitter);
    }

    public function testSplitSetsCorrectVerticesOnChildren(): void
    {
        $portalsGno = [
            [0.0, 0.0],  // vertex 0
            [2.0, 0.0],  // vertex 1
            [1.0, 2.0],  // vertex 2
            [1.0, 0.5],  // interior — index 3
        ];

        $fld = new FieldTriangle([0, 1, 2]);
        $fld->getContents($portalsGno);
        $fld->split();

        $s = $fld->splitter;
        // child[0]: exterior, vertices=[s, vertices[1], vertices[2]]
        $this->assertTrue($fld->children[0]->exterior);
        $this->assertSame($s, $fld->children[0]->vertices[0]);
        // child[1] and [2] are not exterior
        $this->assertFalse($fld->children[1]->exterior);
        $this->assertFalse($fld->children[2]->exterior);
    }

    public function testAssignFieldsToLinksPopulatesFieldOnLastLink(): void
    {
        // Simple triangle: 3 portals, 3 edges forming a field
        $graph = new Graph();
        $graph->addNode(0);
        $graph->addNode(1);
        $graph->addNode(2);
        $graph->addLink(0, 1); // order 0
        $graph->addLink(0, 2); // order 1
        $graph->addLink(1, 2); // order 2 — last

        $fld = new FieldTriangle([0, 1, 2]);
        $fld->assignFieldsToLinks($graph);

        // Last link (order 2: 1→2) should have a field entry
        $lastLink = $graph->getLink(1, 2);
        $this->assertCount(1, $lastLink->fields);
        $this->assertContains(0, $lastLink->fields[0]);
        $this->assertContains(1, $lastLink->fields[0]);
        $this->assertContains(2, $lastLink->fields[0]);
    }
}
