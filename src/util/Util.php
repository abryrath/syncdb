<?php

namespace unionco\syncdb\util;

use Dotenv\Dotenv;
use unionco\syncdb\SyncDb;
use unionco\syncdb\util\LoggerInterface;

class Util
{
    private static $loaded = false;

    private static function loadEnv()
    {
        $baseDir = SyncDb::$instance->getSettings()->baseDir;
        if ($baseDir) {
            if (!static::$loaded) {
                (new Dotenv($baseDir))->load();
                static::$loaded = true;
            }
        }
    }

    public static function checkBackupPath()
    {
        $backupPath = SyncDb::$instance->getSettings()
            ->sqlDumpPath(false);
        //$backupPath = static::sqlDumpPath();
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0777, true);
        }
    }

    public static function env($key, $default = null)
    {
        static::loadEnv();

        // first try normal env
        $value = getenv($key);

        // if normal env is false, try site specific env
        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;

            case 'false':
            case '(false)':
                return false;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return;
        }

        // if (strlen($value) > 1 && StringHelper::startsWith($value, '"') && StringHelper::endsWith($value, '"')) {
        //     return substr($value, 1, -1);
        // }

        return $value;
    }

    public static function checkExecutable(string $path): bool
    {
        $cmd = "which {$path}";
        $output = null;
        $returnVar = null;

        exec($cmd, $output, $returnVar);

        if ($returnVar != 0) {
            return false;
        }

        return true;
    }

    public static function storagePath($path = null)
    {
        $storagePath = SyncDb::$instance->getSettings()->storagePath;
        if ($path) {
            return $storagePath . '/' . trim($path, '/');
        }

        return $storagePath . '/';
    }

    public static function exec(Command $command, LoggerInterface $logger = null)
    {
        //var_dump(get_class_methods(Logger::class));
        //var_dump($logger);
        $silent = false;
        $failOnError = true;

        $cmd = $command->getCommand();
        $timing = $command->getTiming();
        $log = $command->getLogging();
        
        $startTime = null;
        $endTime = null;
        if ($timing && $logger) {
            $logger->log("Beginning {$timing}");
            $startTime = microtime(true);
        }

        if ($silent) {
            $cmd = $cmd . " 2>&1";
        }

        if ($logger) {
            $logger->logCmd($cmd . PHP_EOL);
        }

        $output = null;
        $returnVar = null;

        exec($cmd, $output, $returnVar);

        $logger->logOutput($output);

        if ($returnVar != 0) {
            var_dump($output);
            var_dump($returnVar);
            throw new \Exception("return non-zero:" . print_r($output, true));
        }

        if ($timing && $logger) {
            $endTime = microtime(true);
            $diffTime = number_format(($endTime - $startTime), 2);
            $logger->log("Task {$timing} completed in {$diffTime} seconds" . PHP_EOL);
        }

        if ($log && $logger) {
            $logger->log($log . PHP_EOL);
        }

        sleep(1);
    }
}
