<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DisableToolbarSubscriber implements EventSubscriberInterface
{
    private array $routesWithoutToolbar = [
        'maxfield_export_mobile',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

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
            if (strpos($content, '</body></html>') === false) {
                if (strpos($content, '</body>') === false) {
                    $content .= '</body></html>';
                } else {
                    $content = str_replace('</body>', '</body></html>', $content);
                }
            }
            
            $response->setContent($content);
        }
    }
}