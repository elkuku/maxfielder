<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\Exception\Configuration\InvalidConfigurationException;
use Rector\PHPUnit\CodeQuality\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\Symfony\Bridge\Symfony\Routing\SymfonyRoutesProvider;
use Rector\Symfony\Contract\Bridge\Symfony\Routing\SymfonyRoutesProviderInterface;

try {
    return RectorConfig::configure()
        ->withPaths([
            __DIR__.'/src',
            __DIR__.'/tests',
        ])
        ->withSkip([
            __DIR__.'/tests/bootstrap.php',
            // Only adds @see docblocks pointing at test classes
            AddSeeTestAnnotationRector::class,
            RecastingRemovalRector::class => [
                __DIR__.'/src/Entity/User.php',
            ],
        ])

        ->withSymfonyContainerXml(__DIR__.'/var/cache/dev/App_KernelDevDebugContainer.xml')
        ->withSymfonyContainerPhp(__DIR__.'/tests/symfony-container.php')
        ->registerService(SymfonyRoutesProvider::class, SymfonyRoutesProviderInterface::class)
        ->withPhpSets(php85: true)
        ->withPreparedSets(
            deadCode: true,
            codeQuality: true,
            codingStyle: true,
            typeDeclarations: true,
            privatization: true,
            rectorPreset: true,
            phpunitCodeQuality: true,
            symfonyCodeQuality: true,
            symfonyConfigs: true,
        )
        ->withAttributesSets(
            symfony: true,
            doctrine: true
        )
        ->withComposerBased(
            twig: true,
            doctrine: true,
            phpunit: true,
            symfony: true
        )
        ->withImportNames(removeUnusedImports: true);
} catch (InvalidConfigurationException $e) {
    echo $e->getMessage();
}
