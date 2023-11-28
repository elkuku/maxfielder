<?php

namespace App\Service;

use App\Entity\Waypoint;
use DirectoryIterator;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This is for https://github.com/tvwenger/maxfield .
 */
class MaxFieldGenerator
{
    protected string $rootDir = '';

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string                         $projectDir,
        #[Autowire('%env(MAXFIELDS_EXEC)%')] private readonly string       $maxfieldExec,
        #[Autowire('%env(MAXFIELD_VERSION)%')] private readonly int        $maxfieldVersion,
        #[Autowire('%env(GOOGLE_API_KEY)%')] private readonly string       $googleApiKey,
        #[Autowire('%env(GOOGLE_API_SECRET)%')] private readonly string    $googleApiSecret,
        #[Autowire('%env(APP_DOCKER_CONTAINER)%')] private readonly string $dockerContainer,
    )
    {
        $this->rootDir = $projectDir . '/public/maxfields';
    }

    /**
     * @param array<string, bool> $options
     */
    public function generate(
        string $projectName,
        string $wayPointList,
        int    $playersNum,
        array  $options
    ): void
    {
        $fileSystem = new Filesystem();

        try {
            $projectRoot = $this->rootDir . '/' . $projectName;
            $fileSystem->mkdir($projectRoot);
            $fileName = $projectRoot . '/' . $projectName . '.waypoints';
            $fileSystem->appendToFile($fileName, $wayPointList);
            $fileSystem->appendToFile(
                $projectRoot . '/portals.txt',
                $wayPointList
            );

            if ($this->dockerContainer) {
                $command = "docker run -v $projectRoot:/app/share -t {$this->dockerContainer}"
                    . " /app/share/portals.txt"
                    . " --outdir /app/share --num_agents $playersNum --output_csv"
                    . ' --num_cpus 0 --num_field_iterations 10 --max_route_solutions 10';
            } else {
                if ($this->maxfieldVersion < 4) {
                    $command = "python {$this->maxfieldExec} $fileName"
                        . " -d $projectRoot -f output.pkl -n $playersNum";
                } else {
                    $command = "{$this->maxfieldExec} $fileName"
                        . " --outdir $projectRoot --num_agents $playersNum --output_csv"
                        . ' --num_cpus 0 --num_field_iterations 10 --max_route_solutions 10';
                }
            }

            if ($this->googleApiKey) {
                $command .= ' --google_api_key ' . $this->googleApiKey;
                $command .= ' --google_api_secret ' . $this->googleApiSecret;
            }

            if ($options['skip_plots']) {
                $command .= ' --skip_plots';
            }

            if ($options['skip_step_plots']) {
                $command .= ' --skip_step_plots';
            }

            $command .= " --verbose > $projectRoot/log.txt 2>&1 &";

            $fileSystem->dumpFile($projectRoot . '/command.txt', $command);

            exec($command);
        } catch (IOExceptionInterface $exception) {
            echo 'An error occurred while creating your directory at '
                . $exception->getPath();
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * @return array<string>
     */
    public function getContentList(string $item): array
    {
        $list = [];

        foreach (new DirectoryIterator($this->rootDir . '/' . $item) as $fileInfo) {
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
            $points = $wayPoint->getLat() . ',' . $wayPoint->getLon();
            $name = str_replace([';', '#'], '', (string)$wayPoint->getName());
            $maxFields[] = $name . '; ' . $_ENV['INTEL_URL']
                . '?ll=' . $points . '&z=1&pll=' . $points;
        }

        return implode("\n", $maxFields);
    }

    public function getImagePath(string $item, string $image): string
    {
        return $this->rootDir . "/$item/$image";
    }

    public function remove(string $item): void
    {
        $fileSystem = new Filesystem();

        $fileSystem->remove($this->rootDir . "/$item");
    }

    public function findFrames(string $item): int
    {
        $path = $this->rootDir . '/' . $item . '/frames';
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
