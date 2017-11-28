<?php


namespace SamIT\Yii2\StaticAssets\helpers;


use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use yii\web\AssetBundle;
use yii\web\AssetManager;

class AssetHelper
{

    public static function isAssetBundle(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        if ($class === AssetBundle::class) {
            return false;
        }

        $rc = new \ReflectionClass($class);
        if (!$rc->isSubclassOf(AssetBundle::class)) {
            return false;
        }

        if ($rc->getMethod('publish')->class !== AssetBundle::class) {
            throw new \Exception("Class $class overrides the publish method");
        }

        return true;
    }

    /**
     * Find all asset bundles in a directory recursively.
     * @param string $baseDir
     * @return array
     */
    public static function findAssetBundles(string $baseDir): array
    {
        // We register an autoloader to handle missing classes.
        $autoLoader = function($class) {
            $namespace = StringHelper::dirname($class);
            $class = StringHelper::basename($class);
            try {
                if (stripos($class, 'interface') !== false) {
                    $code = "namespace $namespace { interface $class{} }";
                } else {
                    $code = "namespace $namespace { class $class{} }";
                }
                eval($code);
            } catch (\Throwable $t) {

            }
        };
        spl_autoload_register($autoLoader);
        try {
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
            );

            $classes = [];
            /** @var \SplFileInfo $file */
            foreach($iter as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                foreach(self::getClassNames($file->getPathname()) as $className) {
                    if (self::isAssetBundle($className)) {
                        $classes[] = $className;
                    }
                };
            }
            return $classes;
        } finally {
            spl_autoload_unregister($autoLoader);
        }

    }

    /**
     * Gets the name of a class in a file.
     * - Assumes there is only 1 class in a file.
     * - Assumes there is only 1 namespace in a file.
     * @param string $file
     * @return array
     */
    public static function getClassNames(string $file): array
    {
        $contents = file_get_contents($file);
//        echo "$file - tokenizing...";
        $tokens = token_get_all($contents);
//        echo "OK\n";
        $namespace = self::parseNameSpace($tokens);

        $excludeClasses = [
            '/test/i'
        ];
        $excludeNamespaces = [
            '/^Composer/',
            '/^Symfony/',
            '/^Codeception/',
            '/^yii\\composer/',
            '/test/i'
        ];
        // Exclude some namespaces.
        foreach($excludeNamespaces as $regex) {
            if (preg_match($regex, $namespace)) {
                return [];
            }

        }
        $classes = [];
        // Parse all classes.
        while (true) {
            $class = self::parseClass($tokens);

            // Exclude some classes.
            if (!isset($class)) {
                break;
            }
            foreach($excludeClasses as $regex) {
                if (preg_match($regex, $namespace)) {
                    continue 2;
                }
            }
            $classes[] = "$namespace\\$class";
        }



        return $classes;
//        $namespace = '/namespace\s*/'
    }

    /**
     * Parse the file until the first namespace.
     * @param array $tokens
     * @return string
     */
    public static function parseNameSpace(array &$tokens): string
    {
        self::popUntil($tokens, [T_NAMESPACE]);
        return implode("", self::popUntil($tokens, [';', '{']));

    }

    public static function parseClass(array &$tokens): ?string
    {
        self::popUntil($tokens, [T_CLASS]);
        $popped = self::popUntil($tokens, ['{', T_EXTENDS, T_IMPLEMENTS]);

        return !empty($popped) ? implode("", $popped) : null;

    }

    protected static function popUntil(array &$tokens, array $markers): array
    {
        $popped = [];

        while (!empty($tokens)) {
            // Peek
            $token = array_shift($tokens);
            if (is_array($token) && $token[0] === T_WHITESPACE) {
                continue;

            }
            $popped[] = is_array($token) ? $token[1] : $token;


            foreach($markers as $marker) {

                if ($token === $marker) {
                    array_pop($popped);
                    return $popped;
                }

                if (is_array($token) && $token[0] === $marker) {
                    array_pop($popped);
                    return $popped;
                }

            }
//            echo count($tokens) . "\n";
        }

        return $popped;

    }

    /**
     * Recursively creates gzip files for all files in the directory.
     * @param string $baseDir
     */
    public static function createGzipFiles(string $baseDir)
    {
        // We do not care about memory usage and assume all files fit in memory.
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );
        /** @var \SplFileInfo $file */
        foreach($iter as $file)
        {
            if ($file->getExtension() !== 'gz') {
                $handle = gzopen($file->getPathname() . '.gz', 'w9');
                gzwrite($handle, file_get_contents($file->getPathname()));
                gzclose($handle);

            }
        }
    }

    public static function publishAssets(AssetManager $assetManager, $baseDir)
    {
        foreach(self::findAssetBundles($baseDir) as $bundle) {
            $assetManager->getBundle($bundle, true);
        }
    }

}