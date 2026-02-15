<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class WayPointHelper
{
    private readonly string $rootDir;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        #[Autowire('%env(INTEL_URL)%')] private readonly string $intelUrl
    )
    {
        $this->rootDir = $projectDir.'/public/wp_images';
    }

    public function getImagePath(string $wpId): string
    {
        return $this->rootDir.'/'.$wpId.'.jpg';
    }

    private function getThumbnailBasePath(): string
    {
        return $this->rootDir.'/thumbs';
    }

    private function defineThumbnailPath(string $wpId): string
    {
        return $this->getThumbnailBasePath().'/'.$wpId.'.jpg';
    }

    public function findImage(?string $wpId): bool|string
    {
        if (!$wpId) {
            return false;
        }

        $fileSystem = new Filesystem();

        if (false === $fileSystem->exists($this->rootDir)) {
            $fileSystem->mkdir($this->rootDir);
        }

        $imagePath = $this->getImagePath($wpId);

        return $fileSystem->exists($imagePath) ? $imagePath : false;
    }

    public function getThumbnailPath(?string $wpId, string $imageUrl): string
    {
        $fileSystem = new Filesystem();

        $path = $this->defineThumbnailPath($wpId);

        if ($fileSystem->exists($path)) {
            return $path;
        }

        if (false === $fileSystem->exists($this->rootDir)) {
            $fileSystem->mkdir($this->rootDir);
        }

        if (false === $fileSystem->exists($this->getThumbnailBasePath())) {
            $fileSystem->mkdir($this->getThumbnailBasePath());
        }

        if (false === $this->findImage($wpId)) {
            $this->checkImage($wpId, $imageUrl);
        }

        $imagePath = $this->findImage($wpId);

        if (!\is_string($imagePath)) {
            throw new \RuntimeException('Image not found for waypoint: '.$wpId);
        }

        return $this->makeThumb($imagePath, $path);
    }

    public function checkImage(
        string $wpId,
        string $imageUrl,
        bool $forceUpdate = false
    ): void
    {
        $imagePath = $this->findImage($wpId);

        if ($imagePath && false === $forceUpdate) {
            return;
        }

        $imagePath = $this->getImagePath($wpId);

        $ch = curl_init($imageUrl);
        if (false === $ch) {
            throw new \UnexpectedValueException(
                'Can not init curl for: '.$imageUrl
            );
        }
        $fp = fopen($imagePath, 'wb');
        if (false === $fp) {
            throw new \UnexpectedValueException(
                'Can not open image file under: '.$imageUrl
            );
        }
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_VERBOSE, true);

        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    public function getIntelUrl(): string
    {
        return $this->intelUrl;
    }

    public function cleanName(string $name): string
    {
        $replacements = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'Ó' => 'O',
            'ú' => 'u',
            'Ú' => 'U',
            'ñ' => 'ni',
            'ü' => 'ue',
            'ä' => 'ae',
            'ö' => 'oe',
        ];

        $name = trim($name);
        $name = str_replace(['.', ',', ';', ':', '"', '\'', '\\'], '', $name);

        $name = str_replace(array_keys($replacements), $replacements, $name);

        return $name;
    }

    /** @param int<1, max> $desiredWidth */
    private function makeThumb(string $srcPath, string $destPath, int $desiredWidth = 60): string
    {
        /* read the source image */
        $sourceImage = imagecreatefromjpeg($srcPath);

        if (false === $sourceImage) {
            throw new \RuntimeException('Cannot read image: '.$srcPath);
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        /* find the "desired height" of this thumbnail, relative to the desired width  */
        $desiredHeight = max(1, (int)floor($height * ($desiredWidth / $width)));

        /* create a new, "virtual" image */
        $virtualImage = imagecreatetruecolor($desiredWidth, $desiredHeight);

        if (false === $virtualImage) {
            throw new \RuntimeException('Cannot create thumbnail image');
        }

        /* copy source image at a resized size */
        imagecopyresampled($virtualImage, $sourceImage, 0, 0, 0, 0, $desiredWidth, $desiredHeight, $width, $height);

        /* create the physical thumbnail image to its destination */
        imagejpeg($virtualImage, $destPath);

        return $destPath;
    }
}
