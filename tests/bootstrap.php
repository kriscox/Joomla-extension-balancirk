<?php

declare(strict_types=1);

define('_JEXEC', 1);

require dirname(__DIR__) . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'CoCoCo\\Component\\Balancirk\\Site\\' => dirname(__DIR__) . '/components/com_balancirk/site/src/',
        'CoCoCo\\Component\\Balancirk\\Administrator\\' => dirname(__DIR__) . '/components/com_balancirk/admin/src/',
        'CoCoCo\\Component\\Balancirk\\Api\\' => dirname(__DIR__) . '/components/com_balancirk/api/src/',
        // Joomlaology shared library (lowercase directory names on disk)
        'Joomlaology\\Traits\\' => dirname(__DIR__) . '/libraries/joomlaology/traits/',
        'Joomlaology\\Classes\\' => dirname(__DIR__) . '/libraries/joomlaology/classes/',
    ];

    foreach ($prefixes as $prefix => $basePath) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }

        $relativeClass = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $basePath . $relativeClass . '.php';

        if (is_file($file)) {
            require_once $file;

            return;
        }
    }
});
