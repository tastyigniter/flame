<?php

namespace Igniter\System\Helpers;

use Igniter\System\Classes\PackageManifest;

class SystemHelper
{
    public static function replaceInEnv(string $search, string $replace)
    {
        $file = base_path().'/.env';

        file_put_contents(
            $file,
            preg_replace('/^'.$search.'(.*)$/m', $replace, file_get_contents($file))
        );

        putenv($replace);
    }

    public static function parsePackageCodes($requires)
    {
        $extensions = collect(resolve(PackageManifest::class)->extensions())->keyBy('package_name');

        return collect($requires)
            ->mapWithKeys(function ($version, $code) use ($extensions) {
                if (str_contains($code, '/'))
                    $code = array_get($extensions->get($code, []), 'code');

                return $code ? [$code => $version] : [];
            })->filter()->all();
    }
}
