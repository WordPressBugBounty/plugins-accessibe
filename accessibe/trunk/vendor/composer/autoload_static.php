<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit68765504e05238b706e2b93f21fe6721
{
    public static $files = array (
        '1f155afceeab454b94183cdd8d5248a6' => __DIR__ . '/..' . '/mixpanel/mixpanel-php/lib/Mixpanel.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit68765504e05238b706e2b93f21fe6721::$classMap;

        }, null, ClassLoader::class);
    }
}
