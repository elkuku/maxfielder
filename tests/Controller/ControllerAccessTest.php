<?php

namespace App\Tests\Controller;

use Elkuku\SymfonyUtils\Test\ControllerBaseTest;

class ControllerAccessTest extends ControllerBaseTest
{
    protected string $controllerRoot = __DIR__.'/../../src/Controller';

    /**
     * @var array<int, string>
     */
    protected array $ignoredFiles
        = [
            '.gitignore',
            'Security/GoogleController.php',
            'Security/GitHubController.php',
        ];

    /**
     * @var array<string, array<string, array<string, int>>>
     */
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
            'maxfield_play2' => [
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
            'waypoint_thumbnail' => [
                'params' => [
                    '{guid}' => 'test',
                ],
            ],
        ];

    public function testAllRoutesAreProtected(): void
    {
        $this->runTests(static::createClient());
    }
}
