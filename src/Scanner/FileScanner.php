<?php

namespace Filipefernandes\LaravelTypeCoverage\Scanner;

use Symfony\Component\Finder\Finder;

class FileScanner
{

    /**
     * Get all PHP files in the specified paths, excluding the ignored paths.
     *
     * @param array $paths Paths to scan for PHP files.
     * @param array $ignore Paths to ignore.
     * @return array List of PHP files found.
     */
    public static function getPhpFiles(array $paths, array $ignore = []): array
    {
        $finder = new Finder();
        $finder->files()->in($paths)->name('*.php');

        foreach ($ignore as $exclude) {
            $finder->notPath($exclude);
        }

        return iterator_to_array($finder);
    }
}
