<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Waypoint;
use App\Factory\WaypointFactory;
use App\Repository\WaypointRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class WaypointRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private WaypointRepository $repo;

    protected function setUp(): void
    {
        /** @var WaypointRepository $repo */
        $repo = self::getContainer()->get(WaypointRepository::class);
        $this->repo = $repo;
    }

    public function testFindByIdsReturnsMatchingWaypoints(): void
    {
        $wp1 = WaypointFactory::createOne(['name' => 'Alpha', 'lat' => 1.0, 'lon' => 2.0]);
        $wp2 = WaypointFactory::createOne(['name' => 'Beta', 'lat' => 3.0, 'lon' => 4.0]);
        WaypointFactory::createOne(['name' => 'Gamma', 'lat' => 5.0, 'lon' => 6.0]);

        $result = $this->repo->findByIds([(int) $wp1->getId(), (int) $wp2->getId()]);

        $this->assertCount(2, $result);
        $names = array_map(fn(Waypoint $w): ?string => $w->getName(), $result);
        $this->assertContains('Alpha', $names);
        $this->assertContains('Beta', $names);
    }

    public function testFindByIdsReturnsEmptyForNoMatch(): void
    {
        WaypointFactory::createOne();

        $result = $this->repo->findByIds([99999]);

        $this->assertSame([], $result);
    }

    public function testFindDetailsByIdsReturnsScalarData(): void
    {
        $wp = WaypointFactory::createOne(['name' => 'Portal A', 'lat' => 48.1, 'lon' => 11.5, 'guid' => 'guid-abc']);

        /** @var array<array{id: int, guid: string, name: string, lat: string, lng: string}> $result */
        $result = $this->repo->findDetailsByIds([(int) $wp->getId()]);

        $this->assertCount(1, $result);
        $this->assertSame('Portal A', $result[0]['name']);
        $this->assertSame('guid-abc', $result[0]['guid']);
        $this->assertEqualsWithDelta(48.1, (float) $result[0]['lat'], 0.0001);
        $this->assertEqualsWithDelta(11.5, (float) $result[0]['lng'], 0.0001);
    }

    public function testFindDetailsByIdsReturnsEmptyForNoMatch(): void
    {
        $result = $this->repo->findDetailsByIds([99999]);

        $this->assertSame([], $result);
    }

    public function testFindLatLonReturnsFormattedStrings(): void
    {
        WaypointFactory::createOne(['lat' => 48.0, 'lon' => 11.0]);
        WaypointFactory::createOne(['lat' => 49.0, 'lon' => 12.0]);

        $result = $this->repo->findLatLon();

        $this->assertCount(2, $result);
        // lat/lon are decimal(10,6) so PostgreSQL returns e.g. "48.000000,11.000000"
        $has48 = array_filter($result, fn(string $s): bool => str_starts_with($s, '48.'));
        $has49 = array_filter($result, fn(string $s): bool => str_starts_with($s, '49.'));
        $this->assertNotEmpty($has48, 'Expected a lat_lon string starting with 48.');
        $this->assertNotEmpty($has49, 'Expected a lat_lon string starting with 49.');
    }

    public function testFindLatLonReturnsEmptyWhenNoWaypoints(): void
    {
        $result = $this->repo->findLatLon();

        $this->assertSame([], $result);
    }

    public function testFindInBoundsReturnsWaypointsWithinBounds(): void
    {
        WaypointFactory::createOne(['lat' => 48.0, 'lon' => 11.0]);
        WaypointFactory::createOne(['lat' => 49.0, 'lon' => 12.0]);
        WaypointFactory::createOne(['lat' => 60.0, 'lon' => 20.0]); // outside

        $result = $this->repo->findInBounds(50.0, 13.0, 47.0, 10.0);

        $this->assertCount(2, $result);
    }

    public function testFindInBoundsReturnsEmptyWhenNoneInBounds(): void
    {
        WaypointFactory::createOne(['lat' => 60.0, 'lon' => 20.0]);

        $result = $this->repo->findInBounds(50.0, 13.0, 47.0, 10.0);

        $this->assertSame([], $result);
    }

    public function testFindInBoundsExcludesBoundaryEdge(): void
    {
        // lat exactly at latMax — included (>=, <=)
        WaypointFactory::createOne(['lat' => 50.0, 'lon' => 11.0]);

        $result = $this->repo->findInBounds(50.0, 13.0, 47.0, 10.0);

        $this->assertCount(1, $result);
    }
}
