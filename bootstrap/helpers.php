<?php

/**
 * SVG helper
 *
 * @param string $src Path to svg in the cp image directory
 * @return string
 */
function svg($src)
{
    return file_get_contents(public_path('assets/svg/' . $src . '.svg'));
}

/**
 * Convert some text to Markdown...
 */
function markdown($text)
{
    return (new ParsedownExtra)->text($text);
}

/**
 * Translate the given message.
 */
function __($key, $replace = [], $locale = null)
{
    static $loaded = [];

    $locale = $locale ?: app('translator')->getLocale();

    if (! isset($loaded[$locale])) {
        $loaded[$locale] = app('files')->exists($full = app('path.lang')."/{$locale}.json")
        ? json_decode(app('files')->get($full), true) : [];
    }

    $line = $loaded[$locale][$key] ?? null;

    if (! isset($line)) {
        $fallback = app('translator')->get($key, $replace, $locale);

        if ($fallback !== $key) {
            return $fallback;
        }
    }

    return $line ?: $key;
}
