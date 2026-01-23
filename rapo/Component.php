<?php

namespace Rapo;

abstract class Component {
    protected $props;
    protected $state = [];
    protected static $registeredAssets = [];

    public function __construct(array $props = []) {
        $this->props = $props;
    }

    abstract public function view(): string;

    protected function useAsset(string $path, string $type = 'js') {
        $fullUrl = asset($path);
        if (!isset(self::$registeredAssets[$type])) self::$registeredAssets[$type] = [];
        if (!in_array($fullUrl, self::$registeredAssets[$type])) {
            self::$registeredAssets[$type][] = $fullUrl;
        }
    }

    public static function getRegisteredAssets(string $type = null) {
        if ($type) return self::$registeredAssets[$type] ?? [];
        return self::$registeredAssets;
    }

    public function render(): string {
        $content = $this->view();
        $class = static::class;
        $props = htmlspecialchars(json_encode($this->props));
        
        return "<div data-rapo-component=\"{$class}\" data-rapo-props=\"{$props}\">{$content}</div>" . $this->injectRuntime();
    }

    public function __toString() {
        return $this->render();
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

        // Determine the base URL for the current application
        $scriptName = $_SERVER['SCRIPT_NAME']; 
        $baseUrl = str_replace('\\', '/', dirname($scriptName));
        $liveUrl = rtrim($baseUrl, '/') . '/_rapo/live';

        return <<<HTML
<script>
if (!window.Rapo) {
    window.Rapo = {
        liveUrl: '{$liveUrl}',
        call: async (el, action, extra = {}) => {
            const root = el.closest('[data-rapo-component]');
            const component = root.getAttribute('data-rapo-component');
            const props = root.getAttribute('data-rapo-props');
            
            const form = el.closest('form');
            const dataHash = {};
            if (form) {
                new FormData(form).forEach((value, key) => dataHash[key] = value);
            }
            
            const formData = new FormData();
            formData.append('component', component);
            formData.append('action', action);
            formData.append('props', props);
            formData.append('form_data', JSON.stringify(dataHash));

            // Collect all attributes from the element as extra data
            for (const attr of el.attributes) {
                if (attr.name !== 'rapo-click' && attr.name !== 'rapo-input' && attr.name !== 'rapo-submit') {
                    formData.append(attr.name, attr.value);
                }
            }

            for (const key in extra) {
                formData.append(key, extra[key]);
            }

            try {
                const response = await fetch(window.Rapo.liveUrl, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const html = await response.text();
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const newEl = temp.firstElementChild;
                    
                    // Maintain focus and cursor position for inputs
                    const active = document.activeElement;
                    const activeId = active ? active.id : null;
                    const activeSelector = active ? (activeId ? '#' + activeId : (active.getAttribute('rapo-input') ? '[rapo-input="' + active.getAttribute('rapo-input') + '"]' : null)) : null;
                    const start = active ? active.selectionStart : null;
                    const end = active ? active.selectionEnd : null;
                    const activeValue = active ? active.value : null;

                    root.replaceWith(newEl);

                    if (activeSelector) {
                        const newActive = newEl.querySelector(activeSelector) || document.querySelector(activeSelector);
                        if (newActive) {
                            newActive.focus();
                            // If it's an input and we were typing, don't let the server 
                            // overwrite with an "older" partial state
                            if (activeValue !== null && newActive.value !== activeValue) {
                                // Only overwrite if the change was intended (e.g. from an action)
                                if (extra.state_key) {
                                     newActive.value = activeValue;
                                }
                            }
                            if (start !== null && end !== null) {
                                newActive.setSelectionRange(start, end);
                            }
                        }
                    }
                }
            } catch (e) { console.error('Rapo-Live error:', e); }
        }
    };

    document.addEventListener('click', (e) => {
        const link = e.target.closest('[rapo-link]');
        if (link) {
            e.preventDefault();
            const url = link.getAttribute('rapo-link');
            window.Rapo.navigate(url);
            return;
        }

        const trigger = e.target.closest('[rapo-click]');
        if (trigger) {
            e.preventDefault();
            window.Rapo.call(trigger, trigger.getAttribute('rapo-click'));
        }
    });

    document.addEventListener('submit', (e) => {
        const trigger = e.target.closest('[rapo-submit]');
        if (trigger) {
            e.preventDefault();
            window.Rapo.call(trigger, trigger.getAttribute('rapo-submit'));
        }
    });

    window.Rapo.navigate = async (url) => {
        try {
            const response = await fetch(url, { headers: { 'X-Rapo-Spa': 'true' } });
            const html = await response.text();
            
            if (response.ok || response.status === 404 || response.status === 500) {
                const title = response.headers.get('X-Rapo-Title');
                if (title) document.title = title;

                const main = document.querySelector('main');
                if (main) {
                    main.innerHTML = html;
                    // Scroll to top
                    window.scrollTo(0, 0);
                    // Update URL
                    window.history.pushState({}, '', url);
                } else {
                    // Fallback to full reload if no <main> found
                    window.location.href = url;
                }
            } else {
                window.location.href = url;
            }
        } catch (e) {
            window.location.href = url; 
        }
    };

    window.onpopstate = () => {
        window.location.reload(); // Simple for now
    };

    let inputDebounce;
    document.addEventListener('input', (e) => {
        const trigger = e.target.closest('[rapo-input]');
        if (trigger) {
            const stateKey = trigger.getAttribute('rapo-input');
            clearTimeout(inputDebounce);
            inputDebounce = setTimeout(() => {
                // Collect all current form data to prevent other inputs from being wiped
                const form = trigger.closest('form');
                const dataHash = {};
                if (form) {
                    new FormData(form).forEach((value, key) => dataHash[key] = value);
                }

                window.Rapo.call(trigger, 'syncState', { 
                    state_key: stateKey, 
                    state_value: trigger.value,
                    form_data: JSON.stringify(dataHash)
                });
            }, 250); 
        }
    });

    document.addEventListener('change', (e) => {
        const trigger = e.target.closest('[rapo-input]');
        if (trigger) {
            const stateKey = trigger.getAttribute('rapo-input');
            window.Rapo.call(trigger, 'syncState', { 
                state_key: stateKey, 
                state_value: trigger.value 
            });
        }
    });

    document.addEventListener('submit', (e) => {
        const trigger = e.target.closest('[rapo-submit]');
        if (trigger) {
            e.preventDefault();
            const action = trigger.getAttribute('rapo-submit');
            const formData = new FormData(trigger);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            window.Rapo.call(trigger, action, { form_data: JSON.stringify(data) });
        }
    });
}
</script>
HTML;
    }

    /**
     * Internal method to sync state from client
     */
    public function syncState($data = []) {
        // Update state from form data if provided to keep all inputs in sync
        foreach ($data as $key => $value) {
            $this->useStoreState($key, $value);
        }

        $key = $_POST['state_key'] ?? null;
        $value = $_POST['state_value'] ?? null;
        if ($key !== null) {
            $this->useStoreState($key, $value);
        }
    }

    /**
     * Helper to update both local state and session state
     */
    private function useStoreState($key, $value) {
        $this->state[$key] = $value;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['rapo_state'][static::class][$key] = $value;
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
