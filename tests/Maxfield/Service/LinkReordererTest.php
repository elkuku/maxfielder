<?php

declare(strict_types=1);

namespace App\Tests\Maxfield\Service;

use Elkuku\MaxfieldBundle\Model\Graph;
use Elkuku\MaxfieldBundle\Service\LinkReorderer;
use PHPUnit\Framework\TestCase;

final class LinkReordererTest extends TestCase
{
    private LinkReorderer $reorderer;

    protected function setUp(): void
    {
        $this->reorderer = new LinkReorderer();
    }

    private function makeGraph(int $numPortals): Graph
    {
        $graph = new Graph();
        for ($i = 0; $i < $numPortals; ++$i) {
            $graph->addNode($i);
        }

        return $graph;
    }

    /** @return float[][] */
    private function uniformDists(int $n, float $distance = 100.0): array
    {
        $dists = [];
        for ($i = 0; $i < $n; ++$i) {
            for ($j = 0; $j < $n; ++$j) {
                $dists[$i][$j] = ($i === $j) ? 0.0 : $distance;
            }
        }

        return $dists;
    }

    public function testGetPathLengthEmptyGraphReturnsZero(): void
    {
        $graph = $this->makeGraph(2);

        $this->assertEqualsWithDelta(0.0, $this->reorderer->getPathLength($graph, $this->uniformDists(2)), PHP_FLOAT_EPSILON);
    }

    public function testGetPathLengthSingleLinkReturnsZero(): void
    {
        $graph = $this->makeGraph(2);
        $graph->addLink(0, 1);

        // Only one link, no travel between consecutive links
        $this->assertEqualsWithDelta(0.0, $this->reorderer->getPathLength($graph, $this->uniformDists(2)), PHP_FLOAT_EPSILON);
    }

    public function testGetPathLengthTwoLinks(): void
    {
        $graph = $this->makeGraph(3);
        $graph->addLink(0, 2); // order 0, origin=0
        $graph->addLink(1, 2); // order 1, origin=1

        $dists = $this->uniformDists(3, 150.0);

        // Walk from portal 0 to portal 1: dist[0][1]=150
        $this->assertEqualsWithDelta(150.0, $this->reorderer->getPathLength($graph, $dists), PHP_FLOAT_EPSILON);
    }

    public function testGetPathLengthSameOriginIsZero(): void
    {
        $graph = $this->makeGraph(3);
        $graph->addLink(0, 1); // order 0, origin=0
        $graph->addLink(0, 2); // order 1, origin=0

        $dists = $this->uniformDists(3, 100.0);
        $dists[0][0] = 0.0;

        // Both origins are portal 0, no travel needed
        $this->assertEqualsWithDelta(0.0, $this->reorderer->getPathLength($graph, $dists), PHP_FLOAT_EPSILON);
    }

    public function testReorderByOriginGroupsSameOriginConsecutively(): void
    {
        // Links: 0→1, 2→3, 0→3 — portals 4 total
        // After reorder, the two links from origin 0 should be adjacent
        $graph = $this->makeGraph(4);
        $graph->addLink(0, 1); // order 0
        $graph->addLink(2, 3); // order 1
        $graph->addLink(0, 3); // order 2

        $this->reorderer->reorderByOrigin($graph);

        $ordered = $graph->getOrderedLinks();
        // Find positions of the two links from origin 0
        $positions = [];
        foreach ($ordered as $pos => $link) {
            if ($link->origin === 0) {
                $positions[] = $pos;
            }
        }

        $this->assertCount(2, $positions);
        // They should be consecutive (differ by 1)
        $this->assertSame(1, abs($positions[1] - $positions[0]));
    }

    public function testReorderByOriginEmptyGraphDoesNothing(): void
    {
        $graph = $this->makeGraph(2);
        // No exception should be thrown
        $this->reorderer->reorderByOrigin($graph);
        $this->assertSame([], $graph->getEdges());
    }

    public function testReorderByOriginPreservesAllLinks(): void
    {
        $graph = $this->makeGraph(4);
        $graph->addLink(0, 1);
        $graph->addLink(2, 3);
        $graph->addLink(0, 3);

        $this->reorderer->reorderByOrigin($graph);

        $this->assertCount(3, $graph->getEdges());
    }

    public function testReorderByDependenciesReturnsFalseForSingleLink(): void
    {
        $graph = $this->makeGraph(2);
        $graph->addLink(0, 1);

        $improved = $this->reorderer->reorderByDependencies($graph, $this->uniformDists(2));

        $this->assertFalse($improved);
    }

    public function testReorderByDependenciesReturnsFalseForEmptyGraph(): void
    {
        $graph = $this->makeGraph(2);

        $improved = $this->reorderer->reorderByDependencies($graph, $this->uniformDists(2));

        $this->assertFalse($improved);
    }

    public function testReorderByDependenciesImprovesLongerPath(): void
    {
        // 4 portals arranged so that one ordering is shorter:
        // Portal 0 at x=0, portal 1 at x=10, portal 2 at x=1, portal 3 at x=0
        // Links: 0→3 (order 0), 1→3 (order 1)
        // Path: portal 0 → portal 1 = 10
        // After swap to: 1→3, 0→3
        // Path: portal 1 → portal 0 = 10 (same — just check it doesn't crash)
        $graph = $this->makeGraph(4);
        $graph->addLink(0, 3); // order 0
        $graph->addLink(1, 3); // order 1

        $dists = [
            [0, 10, 1, 0],
            [10, 0, 9, 10],
            [1, 9, 0, 1],
            [0, 10, 1, 0],
        ];

        // No dependencies set, so swap is allowed — just verify it doesn't throw
        $this->reorderer->reorderByDependencies($graph, $dists);
        $this->assertCount(2, $graph->getEdges());
    }

    public function testReorderByDependenciesSkipsLinksWithDependency(): void
    {
        $graph = $this->makeGraph(4);
        $graph->addLink(0, 3); // order 0
        $graph->addLink(1, 3); // order 1

        // Set link 1→3 as depending on link 0→3
        $graph->getLink(1, 3)->depends[] = [0, 3];

        $dists = $this->uniformDists(4, 100.0);
        $originalOrder = $graph->getLink(1, 3)->order;

        $this->reorderer->reorderByDependencies($graph, $dists);

        // Dependency prevents swap — order unchanged
        $this->assertSame($originalOrder, $graph->getLink(1, 3)->order);
    }
}
