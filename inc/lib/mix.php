<?php


/**
 * Get the path to a versioned Mix file.
 *
 * @param string $path
 * @param string $manifestDirectory
 * @return string
 * @throws Exception
 */
function mix($path, $manifestDirectory = '')
{
    static $manifest;

    $publicFolder = '/';

    $rootPath = $_SERVER['DOCUMENT_ROOT'];

    if ($manifestDirectory && ! \substr($manifestDirectory, 0) == '/') {
        $manifestDirectory = "/{$manifestDirectory}";
    }

    if (! $manifest) {
        if (! \file_exists($manifestPath = ($rootPath . $manifestDirectory.'/mix-manifest.json'))) {
            throw new Exception('The Mix manifest does not exist.');
        }
        $manifest = \json_decode(file_get_contents($manifestPath), true);
    }

    if (! \substr($path, 0) == '/') {
        $path = "/{$path}";
    }

    $path = $publicFolder . $path;

    if (! \array_key_exists($path, $manifest)) {
        throw new Exception(
            "Unable to locate Mix file: {$path}. Please check your ".
            'webpack.mix.js output paths and try again.'
        );
    }

    return $manifestDirectory.$manifest[$path];
}
