<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use Throwable;
use App\Enum\UserRole;
use App\Factory\MaxfieldFactory;
use App\Factory\UserFactory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Browser test for the Leaflet-based play route.
 * Verifies the map initialises without browser console errors.
 */
final class MaxfieldPlayBrowserTest extends PantherTestCase
{
    use ResetDatabase;
    use Factories;

    private const string TEST_PATH = 'browser-test-play-maxfield';

    private string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureDir = dirname(__DIR__, 2).'/public/maxfields/'.self::TEST_PATH;
        $this->createFixtureFiles();
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->fixtureDir);
        parent::tearDown();
    }

    private function createFixtureFiles(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->fixtureDir);
        $fs->dumpFile($this->fixtureDir.'/portals_id_map.csv', "0,1,test-guid-001,\"Portal Alpha\"\n");
        $fs->dumpFile($this->fixtureDir.'/portals.txt', "Portal Alpha; https://intel.ingress.com/intel?pll=48.0,11.0\n");
        $fs->dumpFile($this->fixtureDir.'/key_preparation.csv', "KeysNeeded, KeysHave, KeysRemaining, PortalNum, PortalName\n0, 0, 0, 0, Portal Alpha\n");
        $fs->dumpFile($this->fixtureDir.'/agent_assignments.csv', "LinkNum, Agent, OriginNum, OriginName, DestinationNum, DestinationName\n");
        $fs->dumpFile($this->fixtureDir.'/agent_key_preparation.csv', "Agent, PortalNum, PortalName, KeysNeeded\n");
    }

    public function testLeafletPlayPageHasNoBrowserConsoleErrors(): void
    {
        // A user with default settings uses the Leaflet renderer
        $user = UserFactory::createOne(['role' => UserRole::AGENT]);
        MaxfieldFactory::createOne(['owner' => $user, 'path' => self::TEST_PATH]);

        $client = self::createPantherClient(['browser' => self::FIREFOX]);

        // Log in via the form (available in dev/panther envs; no password required)
        $client->request('GET', '/login');
        $client->submitForm('Login', ['identifier' => $user->getIdentifier()]);

        // Navigate to the Leaflet play route
        $client->request('GET', '/maxfield/play/'.self::TEST_PATH);

        // Wait up to 10 s for Leaflet to initialise the map container
        $client->waitFor('.leaflet-container', 10);

        self::assertSelectorExists('.leaflet-container');

        // Arm a console interceptor, then reload so it captures initialisation errors too.
        // Firefox/geckodriver does not expose the WebDriver log API, so this JS approach
        // is the reliable alternative.
        $client->executeScript('
            window.__consoleErrors = [];
            ["error", "warn"].forEach(function(level) {
                var orig = console[level].bind(console);
                console[level] = function() {
                    window.__consoleErrors.push({level: level, message: Array.from(arguments).join(" ")});
                    orig.apply(console, arguments);
                };
            });
            window.addEventListener("error", function(e) {
                window.__consoleErrors.push({level: "error", message: "JS: " + e.message + " (" + e.filename + ":" + e.lineno + ")"});
            });
            window.addEventListener("unhandledrejection", function(e) {
                window.__consoleErrors.push({level: "error", message: "Unhandled rejection: " + String(e.reason)});
            });
        ');

        // Reload — now every console.error/warn and uncaught error is captured from the start.
        $client->reload();
        $client->waitFor('.leaflet-container', 10);

        $client->takeScreenshot('var/screenshots/leaflet-play-success.png');

        /** @var list<array{level: string, message: string}> $captured */
        $captured = $client->executeScript('return window.__consoleErrors || []');

        // Also try the WebDriver log API (populated by Chrome; empty with Firefox/geckodriver).
        try {
            /** @var list<array{level: string, message: string, timestamp: int}> $driverLogs */
            $driverLogs = $client->getWebDriver()->manage()->getLog('browser');
            foreach ($driverLogs as $entry) {
                if ($entry['level'] === 'SEVERE') {
                    $captured[] = ['level' => 'error', 'message' => $entry['message']];
                }
            }
        } catch (Throwable) {
            // Firefox/geckodriver does not expose this API
        }

        $this->assertEmpty($captured, sprintf('Browser console errors/warnings (%d): %s', count($captured), json_encode($captured, \JSON_PRETTY_PRINT)));
    }
}