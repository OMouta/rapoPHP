<?php

namespace Rapo;

class Debug {
    protected static $errors = [];
    protected static $enabled = false;
    protected static $startTime;
    protected static $context = [
        'hierarchy' => [],
        'props' => []
    ];

    public static function enable() {
        if (static::$enabled) return;
        static::$enabled = true;
        static::$startTime = microtime(true);

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

    public static function setContext(array $context) {
        static::$context = array_merge(static::$context, $context);
    }

    public static function renderDevTools() {
        if (!static::$enabled) return '';

        $criticalErrors = array_filter(static::$errors, function($e) {
            return in_array($e['type'], ['Error', 'Parse Error', 'Core Error', 'Compile Error', 'User Error', 'Recoverable Error']);
        });

        if (!empty($criticalErrors)) {
            return self::renderCriticalOverlay($criticalErrors);
        }

        return self::renderToolbar();
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

    public static function renderToolbar() {
        $store = Store::getDefault();
        $request = null;
        $router = null;
        try { $request = $store->get('request'); } catch (\Exception $e) {}
        try { $router = $store->get('router'); } catch (\Exception $e) {}
        
        $errorCount = count(static::$errors);
        $execTime = static::$startTime ? round((microtime(true) - static::$startTime) * 1000, 2) : 0;
        $memUsage = round(memory_get_usage() / 1024 / 1024, 2);
        
        $data = [
            'errors' => static::$errors,
            'route' => [
                'uri' => $request ? $request->getUri() : 'N/A',
                'method' => $request ? $request->getMethod() : 'N/A',
                'params' => $router ? $router->getParams() : [],
            ],
            'context' => static::$context,
            'system' => [
                'php' => PHP_VERSION,
                'memory' => $memUsage . ' MB',
                'time' => $execTime . ' ms',
            ]
        ];

        $json_data = json_encode($data);
        $isSpa = isset($_SERVER['HTTP_X_RAPO_SPA']) || isset($_SERVER['HTTP_X_RAPO_LIVE']);

        $html = '';
        if (!$isSpa) {
            $html .= self::getStyles();
            $html .= self::getStructure($errorCount);
        }

        $html .= "
        <script id='rapo-debug-data' type='application/json'>$json_data</script>
        <script>
        (function() {
            const dataEl = document.getElementById('rapo-debug-data');
            if (!dataEl) return;
            const data = JSON.parse(dataEl.textContent);
            dataEl.remove();

            if (window.updateRapoDebug) {
                window.updateRapoDebug(data);
            } else {
                initRapoDebug(data);
            }

            function initRapoDebug(initialData) {
                const pill = document.querySelector('.rapo-debug-pill');
                const panel = document.querySelector('.rapo-debug-panel');
                if (!pill || !panel) return;

                pill.addEventListener('click', (e) => {
                    e.stopPropagation();
                    panel.classList.toggle('active');
                });
                
                document.addEventListener('click', (e) => {
                    if (!panel.contains(e.target) && !pill.contains(e.target)) {
                        panel.classList.remove('active');
                    }
                });

                const tabs = panel.querySelectorAll('.rapo-debug-tab');
                tabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        tabs.forEach(t => t.classList.remove('active'));
                        tab.classList.add('active');
                        const target = tab.dataset.tab;
                        panel.querySelectorAll('.rapo-tab-content').forEach(c => {
                            c.style.display = c.id === 'tab-' + target ? 'block' : 'none';
                        });
                    });
                });

                window.updateRapoDebug = (newData) => {
                    const count = document.querySelector('.rapo-debug-count');
                    if (count) {
                        count.textContent = newData.errors.length;
                        count.style.display = newData.errors.length > 0 ? 'flex' : 'none';
                        pill.classList.toggle('has-errors', newData.errors.length > 0);
                    }
                    
                    // Errors Content
                    const errorsContainer = document.getElementById('tab-errors');
                    let errorsHtml = '<div class=\"rapo-section-title\">Warnings & Notifications (' + newData.errors.length + ')</div>';
                    if (newData.errors.length === 0) {
                        errorsHtml += '<div class=\"rapo-empty\">No issues detected.</div>';
                    }
                    newData.errors.forEach(err => {
                        errorsHtml += '<div class=\"rapo-error-item\">';
                        errorsHtml += '<div class=\"rapo-error-header\"><span class=\"rapo-tag\">' + err.type + '</span> ' + err.message + '</div>';
                        errorsHtml += '<div class=\"rapo-error-footer\">' + err.file + ':' + err.line + '</div>';
                        errorsHtml += '</div>';
                    });
                    errorsContainer.innerHTML = errorsHtml;

                    // Route Content
                    const routeContainer = document.getElementById('tab-route');
                    let routeHtml = '<div class=\"rapo-section-title\">Current Route</div>';
                    routeHtml += '<div class=\"rapo-info-grid\">';
                    routeHtml += '<div><strong>Method</strong><span>' + newData.route.method + '</span></div>';
                    routeHtml += '<div><strong>URL</strong><span>' + newData.route.uri + '</span></div>';
                    routeHtml += '</div>';
                    routeHtml += '<div class=\"rapo-section-title\" style=\"margin-top:10px\">Parameters</div>';
                    routeHtml += '<pre style=\"font-size:11px; color:#aaa; background:#111; padding:10px; border-radius:4px; margin:0; white-space:pre-wrap;\">' + JSON.stringify(newData.route.params, null, 2) + '</pre>';
                    routeContainer.innerHTML = routeHtml;

                    // Context Content
                    const contextContainer = document.getElementById('tab-context');
                    let contextHtml = '<div class=\"rapo-section-title\">Route Hierarchy</div>';
                    if (newData.context.hierarchy && newData.context.hierarchy.length > 0) {
                        newData.context.hierarchy.forEach((item, i) => {
                            contextHtml += '<div style=\"font-size:12px; margin-bottom:5px; padding-left:' + (i * 10) + 'px; color:' + (i === newData.context.hierarchy.length - 1 ? '#fff' : '#888') + '\"> ';
                            contextHtml += (i > 0 ? '└ ' : '') + item;
                            contextHtml += '</div>';
                        });
                    } else {
                        contextHtml += '<div class=\"rapo-empty\">No hierarchy data.</div>';
                    }
                    
                    contextHtml += '<div class=\"rapo-section-title\" style=\"margin-top:20px\">Page Props</div>';
                    contextHtml += '<pre style=\"font-size:11px; color:#aaa; background:#111; padding:10px; border-radius:4px; margin:0; white-space:pre-wrap;\">' + JSON.stringify(newData.context.props || {}, null, 2) + '</pre>';
                    contextContainer.innerHTML = contextHtml;

                    // System Content
                    const systemContainer = document.getElementById('tab-system');
                    let systemHtml = '<div class=\"rapo-section-title\">Server Information</div>';
                    systemHtml += '<div class=\"rapo-info-grid\">';
                    systemHtml += '<div><strong>PHP Version</strong><span>' + newData.system.php + '</span></div>';
                    systemHtml += '<div><strong>Memory Usage</strong><span>' + newData.system.memory + '</span></div>';
                    systemHtml += '<div><strong>Execution Time</strong><span>' + newData.system.time + '</span></div>';
                    systemHtml += '</div>';
                    systemContainer.innerHTML = systemHtml;
                };

                window.updateRapoDebug(initialData);
            }
        })();
        </script>";

        return $html;
    }

    protected static function getStyles() {
        return "
        <style>
            .rapo-debug-pill {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 44px;
                height: 44px;
                background: #000;
                color: #fff;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 1000000;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                border: 1px solid #333;
                transition: transform 0.2s, background 0.2s;
            }
            .rapo-debug-pill:hover { transform: scale(1.05); background: #111; }
            .rapo-debug-pill.has-errors { border-color: #ff5555; }
            .rapo-debug-count {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #ff5555;
                color: #fff;
                font-size: 10px;
                font-weight: bold;
                min-width: 18px;
                height: 18px;
                padding: 0 4px;
                border-radius: 9px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 2px solid #000;
            }
            .rapo-debug-panel {
                position: fixed;
                bottom: 80px;
                right: 20px;
                width: 380px;
                height: 400px;
                background: #000;
                color: #eee;
                border-radius: 12px;
                border: 1px solid #333;
                z-index: 1000000;
                overflow: hidden;
                display: none;
                flex-direction: column;
                box-shadow: 0 10px 40px rgba(0,0,0,0.5);
                font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;
            }
            .rapo-debug-panel.active { display: flex; }
            .rapo-debug-header {
                padding: 10px 15px;
                border-bottom: 1px solid #222;
                display: flex;
                align-items: center;
                background: #0a0a0a;
            }
            .rapo-debug-tab {
                font-size: 12px;
                color: #666;
                padding: 8px 12px;
                cursor: pointer;
                transition: color 0.2s;
                border-bottom: 2px solid transparent;
            }
            .rapo-debug-tab:hover { color: #fff; }
            .rapo-debug-tab.active { color: #fff; border-bottom-color: #ff5555; }
            .rapo-debug-content {
                padding: 15px;
                overflow-y: auto;
                flex: 1;
            }
            .rapo-section-title {
                font-size: 11px;
                text-transform: uppercase;
                color: #666;
                letter-spacing: 0.5px;
                margin-bottom: 12px;
                font-weight: bold;
            }
            .rapo-error-item {
                background: #111;
                border: 1px solid #222;
                border-radius: 6px;
                padding: 10px;
                margin-bottom: 10px;
            }
            .rapo-error-header { font-size: 12px; color: #eee; margin-bottom: 5px; line-height: 1.4; }
            .rapo-error-footer { font-size: 10px; color: #555; font-family: monospace; word-break: break-all; }
            .rapo-tag {
                background: #ff555522;
                color: #ff5555;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: bold;
                margin-right: 5px;
            }
            .rapo-empty { color: #555; font-size: 12px; text-align: center; padding: 20px 0; }
            .rapo-info-grid { display: grid; gap: 8px; font-size: 12px; }
            .rapo-info-grid > div { display: flex; justify-content: space-between; border-bottom: 1px solid #111; padding-bottom: 4px; }
            .rapo-info-grid strong { color: #888; font-weight: normal; }
            .rapo-info-grid span { color: #fff; font-family: monospace; }
            .rapo-tab-content { display: none; }
        </style>";
    }

    protected static function getStructure($errorCount) {
        $countStyle = $errorCount > 0 ? '' : 'display:none';
        $pillClass = $errorCount > 0 ? 'rapo-debug-pill has-errors' : 'rapo-debug-pill';
        
        return "
        <div class='$pillClass'>
            <svg width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                <path d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'></path>
                <polyline points='3.27 6.96 12 12.01 20.73 6.96'></polyline>
                <line x1='12' y1='22.08' x2='12' y2='12'></line>
            </svg>
            <div class='rapo-debug-count' style='$countStyle'>$errorCount</div>
        </div>
        <div class='rapo-debug-panel'>
            <div class='rapo-debug-header'>
                <div class='rapo-debug-tab active' data-tab='errors'>Errors</div>
                <div class='rapo-debug-tab' data-tab='route'>Route</div>
                <div class='rapo-debug-tab' data-tab='context'>Context</div>
                <div class='rapo-debug-tab' data-tab='system'>System</div>
            </div>
            <div class='rapo-debug-content'>
                <div id='tab-errors' class='rapo-tab-content' style='display:block'></div>
                <div id='tab-route' class='rapo-tab-content'></div>
                <div id='tab-context' class='rapo-tab-content'></div>
                <div id='tab-system' class='rapo-tab-content'></div>
            </div>
        </div>";
    }
}

