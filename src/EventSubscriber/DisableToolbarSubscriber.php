<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DisableToolbarSubscriber
{
    /**
     * @var list<string>
     */
    private array $routesWithoutToolbar = [
        'maxfield_export_mobile',
    ];

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: 0)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        if (!in_array($routeName, $this->routesWithoutToolbar, true)) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();

        if (false === $content) {
            return;
        }

        $patterns = [
            // More aggressive removal - find and remove everything from toolbar comment to end
            '/<!--\s*START of Symfony Web Debug Toolbar\s*-->.*?<!--\s*END of Symfony Web Debug Toolbar\s*-->/s',
            // Also remove any remaining sf-toolbar elements
            '/<div[^>]*class="sf-toolbar[^"]*"[^>]*>.*?<\/div>\s*<\/div>/s',
            '/<link[^>]*href="[^"]*_wdt[^"]*"[^>]*\/?>/',
        ];

        foreach ($patterns as $pattern) {
            // preg_replace() returns null on a regex error; keep the content unchanged then
            $content = preg_replace($pattern, '', $content) ?? $content;
        }

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
