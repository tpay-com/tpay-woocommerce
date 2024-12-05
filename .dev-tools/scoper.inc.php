<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

// Example of collecting files to include in the scoped build but to not scope
// leveraging the isolated finder.
 $excludedFiles = array_map(
     static fn (SplFileInfo $fileInfo) => $fileInfo->getPathName(),
     iterator_to_array(
         Finder::create()->files()->in('../vendor/tpay-com'),
         false,
     ),
 );

return [
    // The prefix configuration. If a non-null value is used, a random prefix
    // will be generated instead.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
    'prefix' => 'Tpay\\Vendor',

    // The base output directory for the prefixed files.
    // This will be overridden by the 'output-dir' command line option if present.
    'output-dir' => '../vendor_prefixed',

    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // This configuration entry is completely ignored when using Box.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
    'finders' => [
        Finder::create()->files()->in('../vendor/composer')->name(['*.php', 'LICENSE', 'composer.json'])->notName('autoload_*.php'),
        Finder::create()->files()->in('../vendor/paragonie')->name(['*.php', 'LICENSE', 'composer.json']),
        Finder::create()->files()->in('../vendor/phpseclib')->name(['*.php', 'LICENSE', 'composer.json']),
        Finder::create()->files()->in('../vendor/psr')->exclude('Test')->name(['*.php', 'LICENSE', 'composer.json']),
    ],

    // List of excluded files, i.e. files for which the content will be left untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'exclude-files' => [
        // 'src/an-excluded-file.php',
//        ...$excludedFiles,
    ],

    // PHP version (e.g. `'7.2'`) in which the PHP parser and printer will be configured into. This will affect what
    // level of code it will understand and how the code will be printed.
    // If none (or `null`) is configured, then the host version will be used.
    'php-version' => null,

    // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
    // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
    // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
    // heart contents.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            if (strpos($contents, '\'phpseclib3\\')) {
                return preg_replace("%'phpseclib3%", "'\Tpay\Vendor\phpseclib3", $contents);
            }

            if (strpos($contents, '\'\\phpseclib3')) {
                return str_replace("'\phpseclib3", "'\Tpay\Vendor\phpseclib3", $contents);
            }

            // Change logger namespaces

            $files = [
                '../vendor/tpay-com/tpay-openapi-php/src/Utilities/Logger.php',
                '../vendor/tpay-com/tpay-openapi-php/src/Utilities/TpayException.php',
            ];

            foreach ($files as $file) {
                if (file_exists($file)) {
                    $content = file_get_contents($file);

                    $changed = str_replace(" Psr\\Log\\", " Tpay\Vendor\Psr\Log\\", $content);

                    file_put_contents($file, $changed);
                }
            }

            // Change phpseclib namespaces

            $files = [
                '../vendor/tpay-com/tpay-openapi-php/src/Utilities/phpseclib/Crypt/RSA.php',
                '../vendor/tpay-com/tpay-openapi-php/src/Utilities/phpseclib/File/X509.php',
            ];

            foreach ($files as $file) {
                if (file_exists($file)) {
                    $content = file_get_contents($file);

                    $changed = str_replace("'phpseclib3\\", "'Tpay\Vendor\phpseclib3\\", $content);
                    $changed = str_replace(" \phpseclib3\\", " \Tpay\Vendor\phpseclib3\\", $changed);
                    $changed = str_replace("'phpseclib\\", "'Tpay\Vendor\phpseclib\\", $changed);
                    $changed = str_replace(" \phpseclib\\", " \Tpay\Vendor\phpseclib\\", $changed);

                    file_put_contents($file, $changed);
                }
            }

            return $contents;
        },
    ],

    // List of symbols to consider internal i.e. to leave untouched.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
    'exclude-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        // '~^$~',                        // The root namespace only
        // '',                            // Any namespace
    ],
    'exclude-classes' => [
        // 'ReflectionClassConstant',
    ],
    'exclude-functions' => [
        // 'mb_str_split',
    ],
    'exclude-constants' => [
        // 'STDIN',
    ],

    // List of symbols to expose.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
    'expose-global-constants' => true,
    'expose-global-classes' => true,
    'expose-global-functions' => true,
    'expose-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        // '~^$~',                        // The root namespace only
        // '',                            // Any namespace
    ],
    'expose-classes' => [],
    'expose-functions' => [],
    'expose-constants' => [],
];
