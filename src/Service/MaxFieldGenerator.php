<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Waypoint;
use App\Enum\MaxfieldEngineEnum;
use DirectoryIterator;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * This is for https://github.com/tvwenger/maxfield .
 */
class MaxFieldGenerator
{
    protected string $rootDir = '';

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%env(MAXFIELDS_EXEC)%')] private readonly string $maxfieldExec,
        #[Autowire('%env(MAXFIELD_VERSION)%')] private readonly int $maxfieldVersion,
        #[Autowire('%env(GOOGLE_API_KEY)%')] private readonly string $googleApiKey,
        #[Autowire('%env(GOOGLE_API_SECRET)%')] private readonly string $googleApiSecret,
        #[Autowire('%env(INTEL_URL)%')] private readonly string $intelUrl,
        #[Autowire('%env(PHP_BINARY)%')] private readonly string $phpBinary = PHP_BINARY,
    )
    {
        $this->rootDir = $projectDir.'/public/maxfields';
    }

    /**
     * @param array<string, bool> $options
     * @param list<array{int, int|null, string|null, string}> $wayPointMap
     */
    public function generate(
        string $projectName,
        string $wayPointList,
        array $wayPointMap,
        int $playersNum,
        array $options,
        MaxfieldEngineEnum $engine = MaxfieldEngineEnum::php,
        string $dockerContainer = '',
    ): void
    {
        $fileSystem = new Filesystem();

        $projectRoot = $this->rootDir.'/'.$projectName;
        $fileSystem->mkdir($projectRoot);
        $fileName = $projectRoot.'/portals.txt';
        $fileSystem->appendToFile($fileName, $wayPointList);

        $fp = fopen($projectRoot.'/portals_id_map.csv', 'w');

        if (false === $fp) {
            throw new RuntimeException('Cannot open file: '.$projectRoot.'/portals_id_map.csv');
        }

        foreach ($wayPointMap as $fields) {
            fputcsv($fp, $fields, escape: '\\');
        }

        fclose($fp);

        $command = $this->buildCommand($projectRoot, $fileName, $playersNum, $options, $engine, $dockerContainer);

        $fileSystem->dumpFile($projectRoot.'/command.txt', implode(' ', $command));

        $process = new Process($command);
        $process->start();
    }

    /**
     * @param array<string, bool> $options
     *
     * @return list<string>
     */
    private function buildCommand(
        string $projectRoot,
        string $fileName,
        int $playersNum,
        array $options,
        MaxfieldEngineEnum $engine = MaxfieldEngineEnum::php,
        string $dockerContainer = '',
    ): array
    {
        $logFile = $projectRoot.'/log.txt';

        if ($engine === MaxfieldEngineEnum::php) {
            $command = [
                $this->phpBinary, $this->projectDir.'/bin/console',
                'maxfield:plan', $fileName,
                '--outdir', $projectRoot,
                '--num-agents', (string) $playersNum,
                '--output-csv',
                '-v',
            ];

            return ['sh', '-c', implode(' ', array_map(escapeshellarg(...), $command)).' > '.escapeshellarg($logFile).' 2>&1'];
        }

        $command = $this->buildExternalCommand($projectRoot, $fileName, $playersNum, $options, $engine, $dockerContainer);

        // Wrap in shell to redirect output to log file and run in background
        return ['sh', '-c', implode(' ', array_map(escapeshellarg(...), $command)).' > '.escapeshellarg($logFile).' 2>&1'];
    }

    /**
     * @param array<string, bool> $options
     *
     * @return list<string>
     */
    private function buildExternalCommand(
        string $projectRoot,
        string $fileName,
        int $playersNum,
        array $options,
        MaxfieldEngineEnum $engine = MaxfieldEngineEnum::python,
        string $dockerContainer = '',
    ): array
    {
        if ($engine === MaxfieldEngineEnum::docker) {
            $command = [
                'docker', 'run',
                '-v', $projectRoot.':/app/share',
                '-t', $dockerContainer,
                '/app/share/portals.txt',
                '--outdir', '/app/share',
                '--num_agents', (string) $playersNum,
                '--output_csv',
                '--num_cpus', '0',
                '--num_field_iterations', '10',
                '--max_route_solutions', '10',
            ];
        } elseif ($this->maxfieldVersion < 4) {
            $command = [
                'python', $this->maxfieldExec, $fileName,
                '-d', $projectRoot,
                '-f', 'output.pkl',
                '-n', (string) $playersNum,
            ];
        } else {
            $command = [
                $this->maxfieldExec, $fileName,
                '--outdir', $projectRoot,
                '--num_agents', (string) $playersNum,
                '--output_csv',
                '--num_cpus', '0',
                '--num_field_iterations', '10',
                '--max_route_solutions', '10',
            ];
        }

        if ($this->googleApiKey !== '' && $this->googleApiKey !== '0') {
            $command[] = '--google_api_key';
            $command[] = $this->googleApiKey;
            $command[] = '--google_api_secret';
            $command[] = $this->googleApiSecret;
        }

        return $this->appendCommandOptions($command, $options);
    }

    /**
     * @param list<string> $command
     * @param array<string, bool> $options
     *
     * @return list<string>
     */
    private function appendCommandOptions(array $command, array $options): array
    {
        if ($options['skip_plots']) {
            $command[] = '--skip_plots';
        }

        if ($options['skip_step_plots']) {
            $command[] = '--skip_step_plots';
        }

        $command[] = '--verbose';

        return $command;
    }

    /**
     * @return array<string>
     */
    public function getContentList(string $item): array
    {
        $list = [];

        foreach (new DirectoryIterator($this->rootDir.'/'.$item) as $fileInfo) {
            if ($fileInfo->isFile()) {
                $list[] = $fileInfo->getFilename();
            }
        }

        sort($list);

        return $list;
    }

    /**
     * @param Waypoint[] $wayPoints
     */
    public function convertWayPointsToMaxFields(array $wayPoints): string
    {
        $maxFields = [];

        foreach ($wayPoints as $wayPoint) {
            $points = $wayPoint->getLat().','.$wayPoint->getLon();
            $name = str_replace([';', '#'], '', (string)$wayPoint->getName());
            $maxFields[] = $name.'; '.$this->intelUrl
                .'?ll='.$points.'&z=1&pll='.$points;
        }

        return implode("\n", $maxFields);
    }

    /**
     * @param Waypoint[] $wayPoints
     * @return list<array{int, int|null, string|null, string}>
     */
    public function getWaypointsMap(array $wayPoints): array
    {
        $map = [];

        foreach ($wayPoints as $i => $wayPoint) {
            $name = str_replace([';', '#', ','], '', (string)$wayPoint->getName());
            $map[] = [$i, $wayPoint->getId(), $wayPoint->getGuid(), $name];
        }

        return $map;
    }

    public function getImagePath(string $item, string $image): string
    {
        return $this->rootDir.sprintf('/%s/%s', $item, $image);
    }

    public function remove(string $item): void
    {
        $fileSystem = new Filesystem();

        $fileSystem->remove($this->rootDir.('/' . $item));
    }

    public function findFrames(string $item): int
    {
        $path = $this->rootDir.'/'.$item.'/frames';
        $frames = 0;

        if (false === file_exists($path)) {
            return $frames;
        }

        foreach (new DirectoryIterator($path) as $file) {
            if (preg_match(
                '/frame_(\d\d\d\d)/',
                $file->getFilename(),
                $matches
            )
            ) {
                $x = (int)$matches[1];
                $frames = max($x, $frames);
            }
        }

        return $frames;
    }

    /**
     * Generate a variant of an existing maxfield by shuffling portal order.
     *
     * @param array<string, bool> $options
     */
    public function generateVariant(
        string $originalProjectName,
        int $playersNum,
        array $options,
        MaxfieldEngineEnum $engine = MaxfieldEngineEnum::php,
        string $dockerContainer = '',
    ): string {
        $originalRoot = $this->rootDir.'/'.$originalProjectName;

        // Read original portals.txt
        $portalsFile = $originalRoot.'/portals.txt';
        if (!file_exists($portalsFile)) {
            throw new RuntimeException('Original portals.txt not found: '.$portalsFile);
        }

        $portalsContent = file_get_contents($portalsFile);
        if ($portalsContent === false) {
            throw new RuntimeException('Cannot read original portals.txt');
        }

        // Read original portals_id_map.csv
        $mapFile = $originalRoot.'/portals_id_map.csv';
        if (!file_exists($mapFile)) {
            throw new RuntimeException('Original portals_id_map.csv not found: '.$mapFile);
        }

        $mapContent = file_get_contents($mapFile);
        if ($mapContent === false) {
            throw new RuntimeException('Cannot read original portals_id_map.csv');
        }

        // Parse portals into array
        $portals = explode("\n", trim($portalsContent));

        // Parse map into array of CSV lines (each line is an array)
        $mapLines = explode("\n", trim($mapContent));
        $mapData = [];
        foreach ($mapLines as $line) {
            $mapData[] = str_getcsv($line, ',', '"', '\\');
        }

        // Shuffle - create indices array and shuffle it
        $indices = range(0, count($portals) - 1);
        shuffle($indices);

        // Rebuild portals.txt with shuffled order
        $shuffledPortals = [];
        $shuffledMap = [];
        foreach ($indices as $i) {
            $shuffledPortals[] = $portals[$i];
            $shuffledMap[] = $mapData[$i];
        }

        // Generate new project name with -v{N} suffix
        $newProjectName = $this->generateVariantName($originalProjectName);

        // Create new directory and write files
        $newRoot = $this->rootDir.'/'.$newProjectName;
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($newRoot);

        // Write shuffled portals.txt
        $fileSystem->dumpFile($newRoot.'/portals.txt', implode("\n", $shuffledPortals));

        // Write shuffled portals_id_map.csv
        $fp = fopen($newRoot.'/portals_id_map.csv', 'w');
        if ($fp === false) {
            throw new RuntimeException('Cannot create portals_id_map.csv in '.$newRoot);
        }
        foreach ($shuffledMap as $fields) {
            fputcsv($fp, $fields, escape: '\\');
        }
        fclose($fp);

        // Build the wayPointList and wayPointMap from shuffled data
        $wayPointList = implode("\n", $shuffledPortals);
        $wayPointMap = $shuffledMap;

        // Generate the variant
        $command = $this->buildCommand($newRoot, $newRoot.'/portals.txt', $playersNum, $options, $engine, $dockerContainer);
        $fileSystem->dumpFile($newRoot.'/command.txt', implode(' ', $command));

        $process = new Process($command);
        $process->start();

        return $newProjectName;
    }

    /**
     * Generate next variant name: "foo-v1", "foo-v2", etc.
     */
    private function generateVariantName(string $originalName): string
    {
        // Check if original already ends with -v{N}
        if (preg_match('/^(.+)-v(\d+)$/', $originalName, $matches)) {
            $baseName = $matches[1];
            $nextNum = (int)$matches[2] + 1;
        } else {
            $baseName = $originalName;
            $nextNum = 1;
        }

        // Find next available number
        $dirs = glob($this->rootDir.'/'.$baseName.'-v*', GLOB_ONLYDIR) ?: [];
        $existingNums = [];
        foreach ($dirs as $dir) {
            if (preg_match('/-v(\d+)$/', $dir, $m)) {
                $existingNums[] = (int)$m[1];
            }
        }

        // Include the nextNum from above in check
        while (in_array($nextNum, $existingNums, true)) {
            $nextNum++;
        }

        return $baseName.'-v'.$nextNum;
    }
}
