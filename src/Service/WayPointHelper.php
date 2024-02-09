<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class WayPointHelper
{
    private readonly string $rootDir;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string              $projectDir,
        #[Autowire('%env(INTEL_URL)%')] private readonly string $intelUrl
    )
    {
        $this->rootDir = $projectDir . '/public/wp_images';
    }

    public function getImagePath(string $wpId): string
    {
        return $this->rootDir . '/' . $wpId . '.jpg';
    }

    private function defineThumbnailPath(string $wpId): string
    {
        return $this->rootDir . '/thumbs/' . $wpId . '.jpg';
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

        if (false === $fileSystem->exists($this->rootDir)) {
            $fileSystem->mkdir($this->rootDir);
        }

        $path = $this->defineThumbnailPath($wpId);

        if ($fileSystem->exists($path)) {
            return $path;
        }

        if (false === $this->findImage($wpId)) {
            $this->checkImage($wpId, $imageUrl);
        }

        return $this->makeThumb($this->findImage($wpId), $path);
    }

    public function checkImage(
        string $wpId,
        string $imageUrl,
        bool   $forceUpdate = false
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
                'Can not init curl for: ' . $imageUrl
            );
        }
        $fp = fopen($imagePath, 'wb');
        if (false === $fp) {
            throw new \UnexpectedValueException(
                'Can not open image file under: ' . $imageUrl
            );
        }
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
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
        ];

        $name = trim($name);
        $name = str_replace(['.', ',', ';', ':', '"', '\'', '\\'], '', $name);

        $name = str_replace(array_keys($replacements), $replacements, $name);

        return $name;
    }

    private function makeThumb(string $src, string $dest, int $desired_width = 40): string
    {
        /* read the source image */
        $source_image = imagecreatefromjpeg($src);
        $width = imagesx($source_image);
        $height = imagesy($source_image);

        /* find the "desired height" of this thumbnail, relative to the desired width  */
        $desired_height = (int)floor($height * ($desired_width / $width));

        /* create a new, "virtual" image */
        $virtual_image = imagecreatetruecolor($desired_width, $desired_height);

        /* copy source image at a resized size */
        imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);

        /* create the physical thumbnail image to its destination */
        imagejpeg($virtual_image, $dest);

        return $dest;
    }
}
