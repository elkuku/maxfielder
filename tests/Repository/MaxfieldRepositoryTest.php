<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Factory\MaxfieldFactory;
use App\Repository\MaxfieldRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class MaxfieldRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private MaxfieldRepository $repo;

    protected function setUp(): void
    {
        /** @var MaxfieldRepository $repo */
        $repo = self::getContainer()->get(MaxfieldRepository::class);
        $this->repo = $repo;
    }

    public function testSearchWithNoTermReturnsAll(): void
    {
        MaxfieldFactory::createOne(['name' => 'Alpha']);
        MaxfieldFactory::createOne(['name' => 'Beta']);

        $result = $this->repo->search();

        $this->assertCount(2, $result);
    }

    public function testSearchWithTermFiltersResults(): void
    {
        MaxfieldFactory::createOne(['name' => 'Downtown Alpha']);
        MaxfieldFactory::createOne(['name' => 'Harbor Beta']);
        MaxfieldFactory::createOne(['name' => 'Alpha North']);

        $result = $this->repo->search('Alpha');

        $this->assertCount(2, $result);
        foreach ($result as $m) {
            $this->assertStringContainsStringIgnoringCase('Alpha', (string) $m->getName());
        }
    }

    public function testSearchIsCaseInsensitive(): void
    {
        MaxfieldFactory::createOne(['name' => 'Downtown']);

        $this->assertCount(1, $this->repo->search('downtown'));
        $this->assertCount(1, $this->repo->search('DOWNTOWN'));
        $this->assertCount(1, $this->repo->search('Downtown'));
    }

    public function testSearchReturnsEmptyForNoMatch(): void
    {
        MaxfieldFactory::createOne(['name' => 'Alpha']);

        $result = $this->repo->search('zzznomatch');

        $this->assertSame([], $result);
    }

    public function testSearchResultsOrderedByName(): void
    {
        MaxfieldFactory::createOne(['name' => 'Zebra']);
        MaxfieldFactory::createOne(['name' => 'Alpha']);
        MaxfieldFactory::createOne(['name' => 'Mango']);

        $result = $this->repo->search();

        $this->assertSame('Alpha', $result[0]->getName());
        $this->assertSame('Mango', $result[1]->getName());
        $this->assertSame('Zebra', $result[2]->getName());
    }

    public function testSearchReturnsEmptyForEmptyDatabase(): void
    {
        $result = $this->repo->search();

        $this->assertSame([], $result);
    }
}
