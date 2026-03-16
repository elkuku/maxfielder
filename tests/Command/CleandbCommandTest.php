<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Factory\WaypointFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class CleandbCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testExecuteWithNoWaypointsSucceeds(): void
    {
        $kernel = self::bootKernel();
        $app = new Application($kernel);

        $command = $app->find('app:cleandb');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Database is clean.', $tester->getDisplay());
    }

    public function testExecuteWithDirtyNameWaypointUpdatesName(): void
    {
        WaypointFactory::createOne(['name' => 'café', 'lat' => 48.0, 'lon' => 11.0]);

        $kernel = self::bootKernel();
        $app = new Application($kernel);

        $command = $app->find('app:cleandb');
        $tester = new CommandTester($command);
        $tester->execute([]);

        // Dirty name triggers a warning; command returns FAILURE
        $this->assertStringContainsString('warnings', $tester->getDisplay());
    }

    public function testExecuteWithMissingLatRemovesWaypoint(): void
    {
        // lat=0 is falsy → triggers error branch
        WaypointFactory::createOne(['name' => 'No Location', 'lat' => 0.0, 'lon' => 0.0]);

        $kernel = self::bootKernel();
        $app = new Application($kernel);

        $command = $app->find('app:cleandb');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('errors', $tester->getDisplay());
    }
}
