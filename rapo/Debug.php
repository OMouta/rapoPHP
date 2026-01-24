<?php

namespace Rapo;

class Debug {
    protected static $errors = [];
    protected static $enabled = false;

    public static function enable() {
        if (static::$enabled) return;
        static::$enabled = true;

        error_reporting(E_ALL);
        ini_set('display_errors', 0);

        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError($errno, $errstr, $errfile, $errline, $trace = null) {
        if (!(error_reporting() & $errno)) return false;

        static::$errors[] = [
            'type' => self::getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'trace' => $trace ?: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];
        return true;
    }

    public static function handleShutdown() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            static::handleError($error['type'], $error['message'], $error['file'], $error['line']);
            
            // For fatal errors, we try to output the dev tools if nothing was sent yet
            if (static::getErrors()) {
                echo static::renderDevTools();
            }
        }
    }

    protected static function getErrorType($errno) {
        $types = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
        ];
        return $types[$errno] ?? 'Unknown Error';
    }

    public static function getErrors() {
        return static::$errors;
    }

    public static function renderDevTools() {
        if (!static::$enabled || empty(static::$errors)) return '';

        $criticalErrors = array_filter(static::$errors, function($e) {
            return in_array($e['type'], ['Error', 'Parse Error', 'Core Error', 'Compile Error', 'User Error', 'Recoverable Error']);
        });

        $hasCritical = !empty($criticalErrors);
        $errorCount = count(static::$errors);

        if ($hasCritical) {
            return self::renderCriticalOverlay($criticalErrors);
        }

        return self::renderWarningPill($errorCount);
    }

    protected static function renderCriticalOverlay($errors) {
        $errorList = '';
        foreach ($errors as $error) {
            $traceHtml = '';
            if (!empty($error['trace'])) {
                $traceHtml = "<div style='margin-top: 15px; font-size: 0.8em; color: #888;'>";
                foreach ($array = array_slice($error['trace'], 0, 10) as $i => $step) {
                    $file = isset($step['file']) ? basename($step['file']) : '[internal]';
                    $line = $step['line'] ?? '?';
                    $func = $step['function'] ?? 'unknown';
                    $traceHtml .= "<div style='margin-bottom: 4px;'>#$i $file($line): $func()</div>";
                }
                $traceHtml .= "</div>";
            }

            $errorList .= "<div style='margin-bottom: 30px;'>
                <div style='color: #ff5555; font-size: 1.2em; font-weight: bold; margin-bottom: 10px;'>{$error['type']}: " . htmlspecialchars($error['message']) . "</div>
                <div style='color: #eee; font-family: monospace; background: #111; padding: 15px; border-radius: 4px; border-left: 4px solid #ff5555;'>
                    <div style='color: #66d9ef;'>{$error['file']} : {$error['line']}</div>
                    $traceHtml
                </div>
            </div>";
        }

        return "
        <div id='rapo-runtime-overlay' style='position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); color: #eee; z-index: 100000; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; padding: 50px; box-sizing: border-box; overflow-y: auto;'>
            <div style='max-width: 800px; margin: 0 auto;'>
                <div style='display: flex; align-items: center; margin-bottom: 40px; border-bottom: 1px solid #333; padding-bottom: 20px;'>
                    <div style='background: #ff5555; color: white; padding: 5px 12px; border-radius: 4px; font-weight: bold; margin-right: 15px;'>RAPO RUNTIME ERROR</div>
                    <span style='color: #666;'>Something went wrong during execution</span>
                </div>
                $errorList
                <button onclick='window.location.reload()' style='background: #333; color: #eee; border: 1px solid #555; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px;'>Reload Page</button>
            </div>
        </div>";
    }

    protected static function renderWarningPill($count) {
        $errorHtml = '';
        foreach (static::$errors as $error) {
            $errorHtml .= "<div style='margin-bottom: 10px; border-bottom: 1px solid #444; padding-bottom: 5px;'>
                <strong style='color: #ffb86c'>[{$error['type']}]</strong> " . htmlspecialchars($error['message']) . "<br>
                <small style='color: #888'>in {$error['file']} on line {$error['line']}</small>
            </div>";
        }

        return "
        <div id='rapo-runtime-pill' style='position: fixed; bottom: 15px; right: 15px; z-index: 99999; font-family: sans-serif;'>
            <div id='rapo-pill-header' style='background: #222; color: #eee; padding: 8px 15px; border-radius: 20px; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.4); display: flex; align-items: center; border: 1px solid #444;'>
                <span style='color: #ff5555; margin-right: 8px;'>●</span>
                <strong style='font-size: 12px;'>Rapo Runtime</strong>
                <span style='background: #ff5555; color: white; border-radius: 10px; padding: 0 6px; font-size: 10px; margin-left: 8px;'>$count</span>
            </div>
            <div id='rapo-pill-body' style='display: none; position: absolute; bottom: 45px; right: 0; background: #222; color: #eee; padding: 15px; border-radius: 8px; width: 350px; max-height: 400px; overflow-y: auto; box-shadow: 0 0 20px rgba(0,0,0,0.5); border: 1px solid #444;'>
                <div style='font-weight: bold; margin-bottom: 15px; color: #ffb86c; border-bottom: 1px solid #444; padding-bottom: 5px;'>Warnings & Notices</div>
                $errorHtml
            </div>
        </div>
        <script>
            (function() {
                const header = document.getElementById('rapo-pill-header');
                const body = document.getElementById('rapo-pill-body');
                if (header && body) {
                    header.addEventListener('click', () => {
                        body.style.display = body.style.display === 'none' ? 'block' : 'none';
                    });
                }
            })();
        </script>";
    }
}
