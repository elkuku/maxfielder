<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

// Lets phpstan/phpstan-doctrine read the entity mapping to infer query result types.
require __DIR__.'/../vendor/autoload.php';

new Dotenv()->bootEnv(__DIR__.'/../.env');

$kernel = new Kernel('test', false);
$kernel->boot();

$doctrine = $kernel->getContainer()->get('doctrine');

if (!$doctrine instanceof ManagerRegistry) {
    throw new RuntimeException('The "doctrine" service is not a ManagerRegistry.');
}

return $doctrine->getManager();
