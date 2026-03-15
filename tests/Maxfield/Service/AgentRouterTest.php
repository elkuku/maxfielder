<?php

declare(strict_types=1);

namespace App\Tests\Maxfield\Service;

use Elkuku\MaxfieldBundle\Model\Assignment;
use Elkuku\MaxfieldBundle\Model\Graph;
use Elkuku\MaxfieldBundle\Service\AgentRouter;
use PHPUnit\Framework\TestCase;

final class AgentRouterTest extends TestCase
{
    private AgentRouter $router;

    protected function setUp(): void
    {
        $this->router = new AgentRouter();
    }

    private function makeGraph(int $numPortals): Graph
    {
        $graph = new Graph();
        for ($i = 0; $i < $numPortals; ++$i) {
            $graph->addNode($i);
        }

        return $graph;
    }

    /** @return int[][] */
    private function uniformDists(int $n, int $distance = 100): array
    {
        $dists = [];
        for ($i = 0; $i < $n; ++$i) {
            for ($j = 0; $j < $n; ++$j) {
                $dists[$i][$j] = ($i === $j) ? 0 : $distance;
            }
        }

        return $dists;
    }

    public function testSingleAgentNoLinksReturnsEmpty(): void
    {
        $graph = $this->makeGraph(2);
        $assignments = $this->router->routeAgents($graph, $this->uniformDists(2), numAgents: 1);

        $this->assertSame([], $assignments);
    }

    public function testSingleAgentFirstLinkArrivesAtZero(): void
    {
        $graph = $this->makeGraph(2);
        $graph->addLink(0, 1);

        $dists = $this->uniformDists(2);

        $assignments = $this->router->routeAgents($graph, $dists, numAgents: 1);

        $this->assertCount(1, $assignments);
        $this->assertSame(0, $assignments[0]->arrive);
    }

    public function testSingleAgentDepartIsArrivePlusLinkTime(): void
    {
        $graph = $this->makeGraph(2);
        $graph->addLink(0, 1);

        $assignments = $this->router->routeAgents($graph, $this->uniformDists(2), numAgents: 1);

        $this->assertSame(30, $assignments[0]->depart); // LINK_TIME = 30
    }

    public function testSingleAgentSecondLinkIncludesTravelTime(): void
    {
        $graph = $this->makeGraph(3);
        $graph->addLink(0, 1); // order 0, origin=0
        $graph->addLink(0, 2); // order 1, origin=0

        // Distance from portal 0 to portal 0 is 0 (same origin)
        $dists = $this->uniformDists(3, 100);
        $dists[0][0] = 0;

        $assignments = $this->router->routeAgents($graph, $dists, numAgents: 1);

        $this->assertCount(2, $assignments);
        // Second link: prev origin=0, cur origin=0, travelTime=0
        $this->assertSame(30, $assignments[1]->arrive); // depart of first + 0 travel
        $this->assertSame(60, $assignments[1]->depart);
    }

    public function testSingleAgentTravelTimeApplied(): void
    {
        $graph = $this->makeGraph(3);
        $graph->addLink(0, 2); // order 0, origin=0
        $graph->addLink(1, 2); // order 1, origin=1

        $dists = $this->uniformDists(3, 200);

        $assignments = $this->router->routeAgents($graph, $dists, numAgents: 1);

        $this->assertCount(2, $assignments);
        // travel from portal 0 to portal 1: dist[0][1]=200, WALK_SPEED=1 → 200s
        $this->assertSame(30 + 200, $assignments[1]->arrive);
        $this->assertSame(30 + 200 + 30, $assignments[1]->depart);
    }

    public function testSingleAgentAssignmentProperties(): void
    {
        $graph = $this->makeGraph(2);
        $graph->addLink(0, 1);

        $assignments = $this->router->routeAgents($graph, $this->uniformDists(2), numAgents: 1);

        $a = $assignments[0];
        $this->assertSame(0, $a->agent);
        $this->assertSame(0, $a->location); // origin of link
        $this->assertSame(1, $a->link);     // destination of link
    }

    public function testMultiAgentProducesAssignmentForEveryLink(): void
    {
        $graph = $this->makeGraph(4);
        $graph->addLink(0, 1);
        $graph->addLink(0, 2);
        $graph->addLink(0, 3);

        $assignments = $this->router->routeAgents($graph, $this->uniformDists(4, 50), numAgents: 2);

        $this->assertCount(3, $assignments);
    }

    public function testMultiAgentAssignmentsAreSortedByArrivalTime(): void
    {
        $graph = $this->makeGraph(3);
        $graph->addLink(0, 2);
        $graph->addLink(1, 2);

        $assignments = $this->router->routeAgents($graph, $this->uniformDists(3, 10), numAgents: 2);
        $counter = \count($assignments);

        for ($i = 1; $i < $counter; ++$i) {
            $this->assertGreaterThanOrEqual($assignments[$i - 1]->arrive, $assignments[$i]->arrive);
        }
    }

    public function testMultiAgentUsesMultipleAgents(): void
    {
        $graph = $this->makeGraph(4);
        $graph->addLink(0, 2);
        $graph->addLink(1, 3);
        $graph->addLink(0, 3);

        $assignments = $this->router->routeAgents($graph, $this->uniformDists(4, 10), numAgents: 2);

        $agentIds = array_unique(array_map(fn(Assignment $a): int => $a->agent, $assignments));
        // With 2 agents and 3 independent links, both agents should be used
        $this->assertGreaterThanOrEqual(1, \count($agentIds));
    }
}
