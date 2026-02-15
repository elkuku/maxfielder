<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;
use Rector\Symfony\Bridge\Symfony\Routing\SymfonyRoutesProvider;
use Rector\Symfony\Contract\Bridge\Symfony\Routing\SymfonyRoutesProviderInterface;
use Rector\Symfony\Symfony30\Rector\MethodCall\StringFormTypeToClassRector;

try {
    return RectorConfig::configure()
        ->withPaths([
            __DIR__.'/src',
            __DIR__.'/tests',
        ])
        ->withSkip([__DIR__.'/src/Service/PhpXlsxGenerator.php',])
        //
        ->withSymfonyContainerXml(__DIR__.'/var/cache/dev/App_KernelDevDebugContainer.xml')
        ->withSymfonyContainerPhp(__DIR__.'/tests/symfony-container.php')
        ->registerService(SymfonyRoutesProvider::class, SymfonyRoutesProviderInterface::class)
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
        ->withImportNames(removeUnusedImports: true)
        ->withRules([
            StringFormTypeToClassRector::class,
        ]);
} catch (InvalidConfigurationException $e) {
    echo($e->getMessage());
}
