<?php

namespace Rapo;

abstract class Component {
    public $props;
    protected $state = [];
    protected static $registeredAssets = [];

    public function __construct(array $props = []) {
        $this->props = $props;
    }

    public function view(): string {
        return '';
    }

    protected function useAsset(string $path, string $type = 'js') {
        $fullUrl = asset($path);
        if (!isset(self::$registeredAssets[$type])) self::$registeredAssets[$type] = [];
        if (!in_array($fullUrl, self::$registeredAssets[$type])) {
            self::$registeredAssets[$type][] = $fullUrl;
        }
    }

    public static function getRegisteredAssets(?string $type = null) {
        if ($type) return self::$registeredAssets[$type] ?? [];
        return self::$registeredAssets;
    }

    public function render(): string {
        $content = $this->view();
        $runtime = $this->injectRuntime();

        // If the content is a full HTML document, don't wrap it in a data-rapo-component div
        if (str_starts_with(trim($content), '<!DOCTYPE') || str_starts_with(trim($content), '<html')) {
            if ($runtime) {
                // Try to inject runtime before </body>
                if (str_contains($content, '</body>')) {
                    return str_replace('</body>', $runtime . '</body>', $content);
                }
            }
            return $content . $runtime;
        }

        $class = static::class;
        $props = htmlspecialchars(json_encode($this->props));
        
        return "<div data-rapo-component=\"{$class}\" data-rapo-props=\"{$props}\">{$content}</div>" . $runtime;
    }

    /**
     * Head hook - manage document head
     */
    protected function useHead(array $config) {
        $head = $this->useStore('head');
        if (isset($config['title'])) $head->setTitle($config['title']);
        if (isset($config['meta'])) {
            foreach ($config['meta'] as $name => $content) {
                $head->addTag("<meta name=\"$name\" content=\"$content\">");
            }
        }
    }

    /**
     * Router hook - access current route and query params
     */
    protected function useRouter() {
        $request = $this->useStore('request');
        $router = $this->useStore('router');
        
        return (object)[
            'push' => fn($url) => redirect($url)->send(),
            'query' => $request->getQueryParams(),
            'params' => $router->getParams() ?? [],
            'pathname' => $request->getUri(),
            'method' => $request->getMethod()
        ];
    }

    /**
     * Session hook - easy session management
     */
    protected function useSession() {
        return $this->useStore('session');
    }

    /**
     * Cache hook - access to the caching layer
     */
    protected function useCache() {
        return $this->useStore('cache');
    }

    /**
     * Storage hook - access to the filesystem
     */
    protected function useStorage() {
        return $this->useStore('storage');
    }

    /**
     * Queue hook - push background jobs
     */
    protected function useQueue() {
        return $this->useStore('queue');
    }

    /**
     * Translation hook - access hierarchical translations
     */
    protected function useTranslation() {
        return $this->useStore('translation');
    }

    /**
     * Store hook - get global services
     */
    protected function useStore($service) {
        return Store::getDefault()->get($service);
    }

    /**
     * Context hook - shares data across components
     */
    protected function useContext(string $name, $defaultValue = null) {
        $store = Store::getDefault();
        try {
            return $store->get("context_$name");
        } catch (\Exception $e) {
            return $defaultValue;
        }
    }

    protected function provideContext(string $name, $value) {
        Store::getDefault()->setShared("context_$name", $value);
    }

    protected function setHead(array $config) {
        $head = $this->useStore('head');
        if (isset($config['title'])) $head->setTitle($config['title']);
        if (isset($config['tags'])) {
            foreach ($config['tags'] as $tag) $head->addTag($tag);
        }
    }

    /**
     * Flash messages hook
     */
    protected function useFlash() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $messages = $_SESSION['rapo_flash'] ?? [];
        $_SESSION['rapo_flash'] = []; // Clear for next request

        return [
            $messages,
            function($type, $message) {
                $_SESSION['rapo_flash'][] = ['type' => $type, 'message' => $message];
            }
        ];
    }

    /**
     * Form handling hook
     */
    protected function useForm(array $initialData = []) {
        [$data, $setData] = $this->useState('form_data', $initialData);
        [$errors, $setErrors] = $this->useState('form_errors', []);

        return [
            'data' => $data,
            'errors' => $errors,
            'setData' => $setData,
            'setErrors' => $setErrors,
            'isValid' => empty($errors)
        ];
    }

    /**
     * Computed hook - derives value from other state
     */
    protected function useComputed(callable $callback, array $dependencies) {
        return $callback();
    }

    protected function injectRuntime() {
        static $injected = false;
        if ($injected) return '';
        $injected = true;

        $scriptName = $_SERVER['SCRIPT_NAME']; 
        $baseUrl = str_replace('\\', '/', dirname($scriptName));
        $liveUrl = rtrim($baseUrl, '/') . '/_rapo/live';
        $csrfToken = csrf_token();

        return <<<HTML
<script>
if (!window.Rapo) {
    window.Rapo = {
        liveUrl: '{$liveUrl}',
        csrfToken: '{$csrfToken}',
        call: async (el, action, extra = {}) => {
            const root = el.closest('[data-rapo-component]');
            const component = root.getAttribute('data-rapo-component');
            const props = root.getAttribute('data-rapo-props');
            
            const formData = new FormData();
            formData.append('component', component);
            formData.append('action', action);
            formData.append('props', props);
            
            if (el.closest('form')) {
                new FormData(el.closest('form')).forEach((v, k) => formData.append(k, v));
            }
            for (const key in extra) formData.append(key, extra[key]);

            try {
                const response = await fetch(window.Rapo.liveUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.Rapo.csrfToken },
                    body: formData
                });

                if (response.ok) {
                    const html = await response.text();
                    const updateUI = () => {
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        root.replaceWith(temp.firstElementChild);
                    };

                    if (document.startViewTransition) {
                        document.startViewTransition(updateUI);
                    } else {
                        updateUI();
                    }
                }
            } catch (e) { console.error('Rapo Live Error:', e); }
        },
        navigate: async (url, push = true) => {
            const updateUI = async () => {
                const res = await fetch(url, { headers: { 'X-Rapo-Spa': 'true' } });
                const html = await res.text();
                const title = res.headers.get('X-Rapo-Title');
                if (title) document.title = title;
                
                const main = document.querySelector('main') || document.body;
                main.innerHTML = html;
                if (push) window.history.pushState({}, '', url);
                window.scrollTo(0, 0);
            };

            if (document.startViewTransition) {
                document.startViewTransition(updateUI);
            } else {
                await updateUI();
            }
        }
    };

    document.addEventListener('click', e => {
        const link = e.target.closest('a');
        if (link && link.href && link.href.startsWith(window.location.origin) && !link.hasAttribute('data-no-spa')) {
            e.preventDefault();
            window.Rapo.navigate(link.href);
        }

        const trigger = e.target.closest('[rapo-click]');
        if (trigger) {
            e.preventDefault();
            window.Rapo.call(trigger, trigger.getAttribute('rapo-click'));
        }
    });

    window.onpopstate = () => window.Rapo.navigate(window.location.pathname + window.location.search, false);
}
</script>
HTML;
    }

    /**
     * Stateful hook that persists in SESSION
     */
    protected function useState(string $key, $initialValue) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $componentName = static::class;
        
        if (!isset($_SESSION['rapo_state'][$componentName][$key])) {
            $_SESSION['rapo_state'][$componentName][$key] = $initialValue;
        }

        $state = $_SESSION['rapo_state'][$componentName][$key];
        
        $setter = function($newValue) use ($componentName, $key) {
            $_SESSION['rapo_state'][$componentName][$key] = $newValue;
        };

        return [$state, $setter];
    }
}
