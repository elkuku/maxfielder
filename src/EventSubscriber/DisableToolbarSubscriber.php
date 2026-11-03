<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DisableToolbarSubscriber
{
    private array $routesWithoutToolbar = [
        'maxfield_export_mobile',
    ];

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: 0)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        if (in_array($routeName, $this->routesWithoutToolbar, true)) {
            $response = $event->getResponse();
            $content = $response->getContent();

            // More aggressive removal - find and remove everything from toolbar comment to end
            $pattern = '/<!--\s*START of Symfony Web Debug Toolbar\s*-->.*?<!--\s*END of Symfony Web Debug Toolbar\s*-->/s';
            $content = preg_replace($pattern, '', $content);

            // Also remove any remaining sf-toolbar elements
            $content = preg_replace('/<div[^>]*class="sf-toolbar[^"]*"[^>]*>.*?<\/div>\s*<\/div>/s', '', $content);
            $content = preg_replace('/<link[^>]*href="[^"]*_wdt[^"]*"[^>]*\/?>/', '', $content);

            // Ensure proper closing tags
            if (!str_contains($content, '</body></html>')) {
                if (!str_contains($content, '</body>')) {
                    $content .= '</body></html>';
                } else {
                    $content = str_replace('</body>', '</body></html>', $content);
                }
            }

            $response->setContent($content);
        }
    }
}
