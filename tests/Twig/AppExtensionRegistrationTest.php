<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\TwigFunction;

final class AppExtensionRegistrationTest extends KernelTestCase
{
    #[TestWith(['hasImage'])]
    #[TestWith(['previewImage'])]
    #[TestWith(['waypointCount'])]
    public function testFunctionIsAvailableInTwig(string $name): void
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $this->assertInstanceOf(TwigFunction::class, $twig->getFunction($name));
    }
}
