<?php

namespace Tests\Helpers;

/**
 * Configure page with device and theme settings for browser tests
 */
function configurePage(string $url, string $device, bool $darkMode)
{
    $page = match ($device) {
        'mobile' => $darkMode ? visit($url)->on()->iPhone14Pro()->inDarkMode() : visit($url)->on()->iPhone14Pro(),
        default => $darkMode ? visit($url)->inDarkMode() : visit($url),
    };

    return $page;
}
