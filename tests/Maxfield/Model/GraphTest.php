<?php

declare(strict_types=1);

namespace App\Tests\Maxfield\Model;

use Elkuku\MaxfieldBundle\Exception\DeadendException;
use Elkuku\MaxfieldBundle\Model\Graph;
use PHPUnit\Framework\TestCase;

final class GraphTest extends TestCase
{
    private Graph $graph;

    protected function setUp(): void
    {
        $this->graph = new Graph();
    }

    public function testAddAndGetNode(): void
    {
        $this->graph->addNode(0, false, 2);
        $node = $this->graph->getNode(0);

        $this->assertFalse($node['sbul']);
        $this->assertSame(2, $node['keys']);
    }

    public function testGetNodeDefaultsForUnknownIndex(): void
    {
        $node = $this->graph->getNode(99);

        $this->assertFalse($node['sbul']);
        $this->assertSame(0, $node['keys']);
    }

    public function testAddNodeWithSbul(): void
    {
        $this->graph->addNode(0, true, 0);
        $this->assertTrue($this->graph->getNode(0)['sbul']);
    }

    public function testHasEdgeAfterAddLink(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addLink(0, 1);

        $this->assertTrue($this->graph->hasEdge(0, 1));
        $this->assertFalse($this->graph->hasEdge(1, 0));
    }

    public function testAddLinkSkipsDuplicateSameDirection(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addLink(0, 1);
        $this->graph->addLink(0, 1); // duplicate

        $this->assertCount(1, $this->graph->getEdges());
    }

    public function testAddLinkSkipsReversedDuplicate(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addLink(0, 1);
        $this->graph->addLink(1, 0); // reversed duplicate

        $this->assertCount(1, $this->graph->getEdges());
    }

    public function testGetLinkReturnsCorrectLink(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addLink(0, 1);

        $link = $this->graph->getLink(0, 1);
        $this->assertSame(0, $link->origin);
        $this->assertSame(1, $link->destination);
    }

    public function testOutDegree(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addNode(2);
        $this->graph->addLink(0, 1);
        $this->graph->addLink(0, 2);

        $this->assertSame(2, $this->graph->outDegree(0));
        $this->assertSame(0, $this->graph->outDegree(1));
    }

    public function testInDegree(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addNode(2);
        $this->graph->addLink(0, 2);
        $this->graph->addLink(1, 2);

        $this->assertSame(2, $this->graph->inDegree(2));
        $this->assertSame(0, $this->graph->inDegree(0));
    }

    public function testCanAddOutboundRespects8LinkLimit(): void
    {
        $this->graph->addNode(0);
        for ($i = 1; $i <= 8; ++$i) {
            $this->graph->addNode($i);
        }

        // Fill up to 8 outgoing links from portal 0
        for ($i = 1; $i <= 8; ++$i) {
            $this->graph->addLink(0, $i);
        }

        $this->assertFalse($this->graph->canAddOutbound(0));
    }

    public function testCanAddOutboundRespectsSbul24LinkLimit(): void
    {
        $this->graph->addNode(0, true); // SBUL portal
        for ($i = 1; $i <= 24; ++$i) {
            $this->graph->addNode($i);
        }

        // Not yet full
        for ($i = 1; $i <= 23; ++$i) {
            $this->graph->addLink(0, $i);
        }

        $this->assertTrue($this->graph->canAddOutbound(0));

        $this->graph->addLink(0, 24);
        $this->assertFalse($this->graph->canAddOutbound(0));
    }

    public function testAddLinkThrowsDeadendWhenBothAtLimit(): void
    {
        // Fill portal 0 and 1 to their 8-link limit with non-reversible links to other portals
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        for ($i = 2; $i <= 9; ++$i) {
            $this->graph->addNode($i);
            $this->graph->addLink(0, $i, reversible: false);
        }

        for ($i = 10; $i <= 17; ++$i) {
            $this->graph->addNode($i);
            $this->graph->addLink(1, $i, reversible: false);
        }

        $this->expectException(DeadendException::class);
        $this->graph->addLink(0, 1, reversible: false);
    }

    public function testAddLinkUsesReversibleDirectionWhenP1Full(): void
    {
        // Fill portal 0 with 8 non-reversible links; portal 1 is free
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        for ($i = 2; $i <= 9; ++$i) {
            $this->graph->addNode($i);
            $this->graph->addLink(0, $i, reversible: false);
        }

        // Try to add link 0→1 with reversible=true; should reverse to 1→0
        $this->graph->addLink(0, 1, reversible: true);

        $this->assertTrue($this->graph->hasEdge(1, 0));
        $this->assertFalse($this->graph->hasEdge(0, 1));
    }

    public function testRemoveEdge(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addLink(0, 1);
        $this->graph->removeEdge(0, 1);

        $this->assertFalse($this->graph->hasEdge(0, 1));
    }

    public function testGetOrderedLinksReturnsSortedByOrder(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addNode(2);
        $this->graph->addLink(0, 1); // order 0
        $this->graph->addLink(0, 2); // order 1

        $links = $this->graph->getOrderedLinks();
        $this->assertSame(0, $links[0]->order);
        $this->assertSame(1, $links[1]->order);
    }

    public function testLinkOrderUpdatedOnAddLink(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addNode(2);
        $this->graph->addLink(0, 1);
        $this->graph->addLink(1, 2);

        $this->assertCount(2, $this->graph->linkOrder);
        $this->assertSame([0, 1], $this->graph->linkOrder[0]);
        $this->assertSame([1, 2], $this->graph->linkOrder[1]);
    }

    public function testDeepCloneIsIndependent(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addLink(0, 1);

        $clone = $this->graph->deepClone();
        $clone->addNode(2);
        $clone->addLink(0, 2);

        // Original is unchanged
        $this->assertFalse($this->graph->hasEdge(0, 2));
        $this->assertCount(1, $this->graph->getEdges());
    }

    public function testDeepCloneCopiesLinkData(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addLink(0, 1);
        $this->graph->getLink(0, 1)->fields = [[0, 1, 2]];

        $clone = $this->graph->deepClone();
        $this->assertSame([[0, 1, 2]], $clone->getLink(0, 1)->fields);
    }

    public function testGetEdgesReturnsAllLinks(): void
    {
        $this->graph->addNode(0);
        $this->graph->addNode(1);
        $this->graph->addNode(2);
        $this->graph->addLink(0, 1);
        $this->graph->addLink(1, 2);

        $this->assertCount(2, $this->graph->getEdges());
    }

    public function testInitialStatistics(): void
    {
        $this->assertSame(-1, $this->graph->ap);
        $this->assertSame(INF, $this->graph->length);
        $this->assertSame(0, $this->graph->maxKeys);
    }
}
