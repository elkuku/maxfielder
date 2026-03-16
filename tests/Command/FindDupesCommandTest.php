<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Factory\WaypointFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class FindDupesCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testExecuteWithNoDuplicatesSucceeds(): void
    {
        WaypointFactory::createOne(['lat' => 48.0, 'lon' => 11.0]);
        WaypointFactory::createOne(['lat' => 49.0, 'lon' => 12.0]);

        $kernel = self::bootKernel();
        $app = new Application($kernel);

        $command = $app->find('app:find-dupes');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('clean', $tester->getDisplay());
    }

    public function testExecuteWithNoDuplicatedGuidSucceeds(): void
    {
        WaypointFactory::createOne(['lat' => 48.0, 'lon' => 11.0, 'guid' => 'guid-unique-1']);
        WaypointFactory::createOne(['lat' => 49.0, 'lon' => 12.0, 'guid' => 'guid-unique-2']);

        $kernel = self::bootKernel();
        $app = new Application($kernel);

        $command = $app->find('app:find-dupes');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
    }
}
