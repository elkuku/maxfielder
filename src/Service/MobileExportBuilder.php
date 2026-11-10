<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Prepares the data and markup for the standalone mobile export page.
 */
class MobileExportBuilder
{
    private const string TOOLBAR_START = '<!-- START of Symfony Web Debug Toolbar -->';

    private const string TOOLBAR_END = '<!-- END of Symfony Web Debug Toolbar -->';

    /**
     * @param array<array-key, mixed> $requestedNames agent names from the request, keyed by agent number
     *
     * @return array<int, string>
     */
    public function buildAgentNames(array $requestedNames, int $numAgents, string $baseAgentName): array
    {
        $agentNames = [];

        for ($i = 1; $i <= $numAgents; ++$i) {
            $agentNames[$i] = $this->agentName($requestedNames[$i] ?? null, $i, $numAgents, $baseAgentName);
        }

        return $agentNames;
    }

    /**
     * Loads the frame_N.gif files of a directory as data URIs, sorted by file name.
     *
     * @return array<string, string>
     */
    public function loadFrames(string $framesDir): array
    {
        $frames = [];

        foreach (glob($framesDir.'/frame_*.gif') ?: [] as $fullPath) {
            $file = basename($fullPath);
            $contents = file_get_contents($fullPath);

            if (false !== $contents && preg_match('/^frame_\d+\.gif$/', $file)) {
                $frames[$file] = 'data:image/gif;base64,'.base64_encode($contents);
            }
        }

        ksort($frames);

        return $frames;
    }

    /**
     * Cuts everything after </body> (where the debug toolbar gets injected) and removes the toolbar itself.
     */
    public function stripDebugToolbar(string $html): string
    {
        $html = $this->cutAfter($html, '</body>', '</body></html>')
            ?? $this->cutAfter($html, '</html>', '</html>')
            ?? $html;

        $toolbarStart = strpos($html, self::TOOLBAR_START);
        $toolbarEnd = false !== $toolbarStart ? strpos($html, self::TOOLBAR_END, $toolbarStart) : false;

        if (false !== $toolbarStart && false !== $toolbarEnd) {
            $html = substr($html, 0, $toolbarStart).substr($html, $toolbarEnd + strlen(self::TOOLBAR_END));
        }

        if (!str_contains($html, '</body>') && !str_contains($html, '</html>')) {
            $html .= '</body></html>';
        }

        return $html;
    }

    private function agentName(mixed $requested, int $number, int $numAgents, string $baseAgentName): string
    {
        if (is_string($requested) && '' !== $requested && '0' !== $requested) {
            return $requested;
        }

        if (1 === $numAgents && '' !== $baseAgentName && '0' !== $baseAgentName) {
            // Single agent: use logged-in user's name
            return $baseAgentName;
        }

        // Multiple agents: use "Agent N" format
        return $numAgents > 1 ? 'Agent '.$number : 'Agent';
    }

    /**
     * Returns the HTML up to the first $marker followed by $replacement, or null if $marker is missing.
     */
    private function cutAfter(string $html, string $marker, string $replacement): ?string
    {
        $position = strpos($html, $marker);

        return false === $position ? null : substr($html, 0, $position).$replacement;
    }
}
