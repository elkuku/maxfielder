<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Override;
use App\Story\DefaultFixturesStory;
use Elkuku\SymfonyUtils\Test\ControllerBaseTest;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ControllerAccessTest extends ControllerBaseTest
{
    use ResetDatabase;
    use Factories;

    #[Override]
    protected string $controllerRoot = __DIR__.'/../../src/Controller';

    /**
     * @var array<int, string>
     */
    #[Override]
    protected array $ignoredFiles
        = [
            '.gitignore',
            'Security/GoogleController.php',
            'Security/GitHubController.php',
        ];

    /**
     * @var array<string, array<string, array<string, int|string>>>
     */
    #[Override]
    protected array $exceptions
        = [
            'default' => [
                'statusCodes' => ['GET' => 200],
            ],
            'login'   => [
                'statusCodes' => ['GET' => 200],
            ],
            'app_portalcalc'   => [
                'statusCodes' => ['GET' => 200],
            ],
            'max_fields_result' => [
                'params' => [
                    '{path}' => 'test',
                ],
            ],
            'maxfield_play' => [
                'params' => [
                    '{path}' => 'test',
                ],
            ],
            'maxfield_submit_user_data' => [
                'params' => [
                    '{path}' => 'test',
                ],
            ],
            'maxfield_get_user_data' => [
                'params' => [
                    '{path}' => 'test',
                ],
            ],
            'maxfield_clear_user_data' => [
                'params' => [
                    '{path}' => 'test',
                ],
            ],
            'maxfield_get_data' => [
                'params' => [
                    '{path}' => 'test',
                ],
            ],
            'maxfield_export_mobile' => [
                'params' => [
                    '{path}' => 'test',
                ],
            ],
            'waypoint_thumbnail' => [
                'params' => [
                    '{guid}' => 'test',
                ],
            ],
        ];

    public function testAllRoutesAreProtected(): void
    {
        $client = self::createClient();
        DefaultFixturesStory::load();
        $this->runTests($client);
    }
}
