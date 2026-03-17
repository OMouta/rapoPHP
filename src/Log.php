<?php

namespace Rapo;

class Log {
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    protected static $logPath;

    public static function setPath(string $path) {
        self::$logPath = $path;
    }

    public static function log($level, $message, array $context = []) {
        if (!self::$logPath) {
            self::$logPath = getcwd() . '/storage/logs/rapo.log';
        }

        $dir = dirname(self::$logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $date = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $formatted = "[$date] " . strtoupper($level) . ": $message$contextStr" . PHP_EOL;

        file_put_contents(self::$logPath, $formatted, FILE_APPEND);
    }

    public static function info($message, array $context = []) {
        self::log(self::INFO, $message, $context);
    }

    public static function error($message, array $context = []) {
        self::log(self::ERROR, $message, $context);
    }

    public static function debug($message, array $context = []) {
        self::log(self::DEBUG, $message, $context);
    }

    public static function warning($message, array $context = []) {
        self::log(self::WARNING, $message, $context);
    }
}
