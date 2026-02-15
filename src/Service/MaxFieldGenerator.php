<?php

namespace App\Service;

use App\Entity\Waypoint;
use DirectoryIterator;
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
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        #[Autowire('%env(MAXFIELDS_EXEC)%')] private readonly string $maxfieldExec,
        #[Autowire('%env(MAXFIELD_VERSION)%')] private readonly int $maxfieldVersion,
        #[Autowire('%env(GOOGLE_API_KEY)%')] private readonly string $googleApiKey,
        #[Autowire('%env(GOOGLE_API_SECRET)%')] private readonly string $googleApiSecret,
        #[Autowire('%env(APP_DOCKER_CONTAINER)%')] private readonly string $dockerContainer,
        #[Autowire('%env(INTEL_URL)%')] private readonly string $intelUrl,
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
        array $options
    ): void
    {
        $fileSystem = new Filesystem();

        $projectRoot = $this->rootDir.'/'.$projectName;
        $fileSystem->mkdir($projectRoot);
        $fileName = $projectRoot.'/portals.txt';
        $fileSystem->appendToFile($fileName, $wayPointList);

        $fp = fopen($projectRoot.'/portals_id_map.csv', 'w');

        if (false === $fp) {
            throw new \RuntimeException('Cannot open file: '.$projectRoot.'/portals_id_map.csv');
        }

        foreach ($wayPointMap as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);

        $command = $this->buildCommand($projectRoot, $fileName, $playersNum, $options);

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
    ): array
    {
        $logFile = $projectRoot.'/log.txt';

        if ($this->dockerContainer) {
            $command = [
                'docker', 'run',
                '-v', $projectRoot.':/app/share',
                '-t', $this->dockerContainer,
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

        if ($this->googleApiKey) {
            $command[] = '--google_api_key';
            $command[] = $this->googleApiKey;
            $command[] = '--google_api_secret';
            $command[] = $this->googleApiSecret;
        }

        if ($options['skip_plots']) {
            $command[] = '--skip_plots';
        }

        if ($options['skip_step_plots']) {
            $command[] = '--skip_step_plots';
        }

        $command[] = '--verbose';

        // Wrap in shell to redirect output to log file and run in background
        return ['sh', '-c', implode(' ', array_map('escapeshellarg', $command)).' > '.escapeshellarg($logFile).' 2>&1'];
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
        return $this->rootDir."/$item/$image";
    }

    public function remove(string $item): void
    {
        $fileSystem = new Filesystem();

        $fileSystem->remove($this->rootDir."/$item");
    }

    public function findFrames(string $item): int
    {
        $path = $this->rootDir.'/'.$item.'/frames';
        $frames = 0;

        if (false === file_exists($path)) {
            return $frames;
        }

        foreach (new \DirectoryIterator($path) as $file) {
            if (preg_match(
                '/frame_(\d\d\d\d\d)/',
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
}
