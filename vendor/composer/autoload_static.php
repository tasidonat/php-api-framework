<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit34bca003cf9be8b11ab03ebea2b7c17c
{
    public static $files = array (
        '404b00a5c8657ac4396981685126c472' => __DIR__ . '/../..' . '/config/database.php',
    );

    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Core\\' => 5,
        ),
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Core\\' => 
        array (
            0 => __DIR__ . '/../..' . '/core',
        ),
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit34bca003cf9be8b11ab03ebea2b7c17c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit34bca003cf9be8b11ab03ebea2b7c17c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit34bca003cf9be8b11ab03ebea2b7c17c::$classMap;

        }, null, ClassLoader::class);
    }
}
