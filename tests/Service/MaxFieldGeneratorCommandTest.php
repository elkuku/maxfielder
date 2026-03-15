<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\MaxFieldGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Filesystem\Filesystem;

final class MaxFieldGeneratorCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/maxfielder_cmd_test_'.uniqid();
        mkdir($this->tempDir.'/public/maxfields', 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDir);
    }

    /**
     * @param array<string, bool> $options
     * @return list<string>
     */
    private function buildCommand(MaxFieldGenerator $generator, string $projectRoot, string $fileName, int $players, array $options): array
    {
        $method = new ReflectionMethod(MaxFieldGenerator::class, 'buildCommand');

        /** @var list<string> */
        return $method->invoke($generator, $projectRoot, $fileName, $players, $options);
    }

    /** @return array<string, bool> */
    private function defaultOptions(): array
    {
        return ['skip_plots' => false, 'skip_step_plots' => false];
    }

    private function makeGenerator(
        string $dockerContainer = '',
        int $version = 4,
        string $usePhp = '0',
        string $googleKey = '',
        string $googleSecret = '',
    ): MaxFieldGenerator {
        return new MaxFieldGenerator(
            $this->tempDir,
            '/usr/bin/maxfield',
            $version,
            $googleKey,
            $googleSecret,
            $dockerContainer,
            'https://intel.ingress.com/intel',
            $usePhp,
        );
    }

    public function testBuildCommandUsesPhpMaxfieldWhenEnabled(): void
    {
        $gen = $this->makeGenerator(usePhp: 'true');
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 2, $this->defaultOptions());

        $this->assertSame('sh', $cmd[0]);
        $this->assertStringContainsString('maxfield:plan', $cmd[2]);
        $this->assertStringContainsString('--num-agents', $cmd[2]);
    }

    public function testBuildCommandUsesPhpMaxfieldWhenEnabledWithOne(): void
    {
        $gen = $this->makeGenerator(usePhp: '1');
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 1, $this->defaultOptions());

        $this->assertStringContainsString('maxfield:plan', $cmd[2]);
    }

    public function testBuildCommandUsesDockerWhenContainerSet(): void
    {
        $gen = $this->makeGenerator(dockerContainer: 'my-container');
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 3, $this->defaultOptions());

        $this->assertStringContainsString('docker', $cmd[2]);
        $this->assertStringContainsString('my-container', $cmd[2]);
        $this->assertStringContainsString('--num_agents', $cmd[2]);
    }

    public function testBuildCommandUsesPythonForVersion3(): void
    {
        $gen = $this->makeGenerator(version: 3);
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 1, $this->defaultOptions());

        $this->assertStringContainsString('python', $cmd[2]);
        $this->assertStringContainsString('-n', $cmd[2]);
    }

    public function testBuildCommandUsesMaxfieldExecForVersion4(): void
    {
        $gen = $this->makeGenerator(version: 4);
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 1, $this->defaultOptions());

        $this->assertStringContainsString('maxfield', $cmd[2]);
        $this->assertStringContainsString('--num_agents', $cmd[2]);
    }

    public function testBuildCommandAppendsGoogleApiKey(): void
    {
        $gen = $this->makeGenerator(googleKey: 'my-key', googleSecret: 'my-secret');
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 1, $this->defaultOptions());

        $this->assertStringContainsString('google_api_key', $cmd[2]);
        $this->assertStringContainsString('my-key', $cmd[2]);
        $this->assertStringContainsString('my-secret', $cmd[2]);
    }

    public function testBuildCommandOmitsGoogleKeyWhenEmpty(): void
    {
        $gen = $this->makeGenerator(googleKey: '');
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 1, $this->defaultOptions());

        $this->assertStringNotContainsString('google_api_key', $cmd[2]);
    }

    public function testBuildCommandAppendsSkipPlots(): void
    {
        $gen = $this->makeGenerator();
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 1, ['skip_plots' => true, 'skip_step_plots' => false]);

        $this->assertStringContainsString('skip_plots', $cmd[2]);
    }

    public function testBuildCommandAppendsSkipStepPlots(): void
    {
        $gen = $this->makeGenerator();
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 1, ['skip_plots' => false, 'skip_step_plots' => true]);

        $this->assertStringContainsString('skip_step_plots', $cmd[2]);
    }

    public function testBuildCommandRedirectsOutputToLogFile(): void
    {
        $gen = $this->makeGenerator();
        $cmd = $this->buildCommand($gen, '/tmp/project', '/tmp/project/portals.txt', 1, $this->defaultOptions());

        $this->assertStringContainsString('log.txt', $cmd[2]);
        $this->assertStringContainsString('2>&1', $cmd[2]);
    }

    public function testGenerateWritesExpectedFiles(): void
    {
        $gen = $this->makeGenerator();
        $projectName = 'test-project-'.uniqid();
        $projectDir = $this->tempDir.'/public/maxfields/'.$projectName;

        $gen->generate(
            $projectName,
            "Portal A; https://intel.ingress.com/intel?pll=1.0,2.0\n",
            [[0, null, 'guid-a', 'Portal A']],
            1,
            $this->defaultOptions(),
        );

        $this->assertFileExists($projectDir.'/portals.txt');
        $this->assertFileExists($projectDir.'/portals_id_map.csv');
        $this->assertFileExists($projectDir.'/command.txt');
        $this->assertStringContainsString('Portal A', (string) file_get_contents($projectDir.'/portals.txt'));
    }

    public function testGenerateWritesCsvMap(): void
    {
        $gen = $this->makeGenerator();
        $projectName = 'test-csv-'.uniqid();

        $gen->generate(
            $projectName,
            "Portal A; https://intel.ingress.com/intel?pll=1.0,2.0\n",
            [[0, 42, 'guid-a', 'Portal A'], [1, 43, 'guid-b', 'Portal B']],
            1,
            $this->defaultOptions(),
        );

        $csv = file_get_contents($this->tempDir.'/public/maxfields/'.$projectName.'/portals_id_map.csv');
        $this->assertStringContainsString('guid-a', (string) $csv);
        $this->assertStringContainsString('guid-b', (string) $csv);
    }
}
