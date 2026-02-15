<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\String\UnicodeString;
use function Symfony\Component\String\u;

/**
 * @method User|null getUser()
 */
class BaseController extends AbstractController
{
    protected function getRefererString(Request $request): UnicodeString
    {
        $referer = (string)$request->headers->get('referer');

        return u($referer);
    }

    protected function getInternalReferer(
        Request $request,
        RouterInterface $router
    ): string
    {
        $referer = $this->getRefererString($request);
        if ($referer->isEmpty()) {
            return '';
        }

        $refererPathInfo = Request::create($referer)->getPathInfo();

        $routeInfos = $router->match($refererPathInfo);

        /** @var string $route */
        $route = $routeInfos['_route'] ?? '';

        return $route;
    }
}
