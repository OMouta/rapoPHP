<?php

namespace Rapo;

abstract class Component {
    protected $props;
    protected $state = [];

    public function __construct(array $props = []) {
        $this->props = $props;
    }

    abstract public function view(): string;

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
            
            const formData = new FormData();
            formData.append('component', component);
            formData.append('action', action);
            formData.append('props', props);

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
                    const start = active ? active.selectionStart : null;
                    const end = active ? active.selectionEnd : null;
                    const activeValue = active ? active.value : null;

                    root.replaceWith(newEl);

                    if (activeId) {
                        const newActive = document.getElementById(activeId);
                        if (newActive) {
                            newActive.focus();
                            // If it's an input and we were typing, don't let the server 
                            // overwrite with an "older" partial state
                            if (activeValue !== null && newActive.value !== activeValue) {
                                // Only overwrite if the change was intended (e.g. from an action)
                                // otherwise keep what the user is currently typing
                                if (!extra.state_key) {
                                     // Action occurred, allow server to change value
                                } else {
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
        const trigger = e.target.closest('[rapo-click]');
        if (trigger) {
            e.preventDefault();
            window.Rapo.call(trigger, trigger.getAttribute('rapo-click'));
        }
    });

    let inputDebounce;
    document.addEventListener('input', (e) => {
        const trigger = e.target.closest('[rapo-input]');
        if (trigger) {
            const stateKey = trigger.getAttribute('rapo-input');
            clearTimeout(inputDebounce);
            inputDebounce = setTimeout(() => {
                window.Rapo.call(trigger, 'syncState', { 
                    state_key: stateKey, 
                    state_value: trigger.value 
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
    public function syncState() {
        $key = $_POST['state_key'] ?? null;
        $value = $_POST['state_value'] ?? null;
        if ($key !== null) {
            $this->useState($key, $value);
            // Ensure the local component state is also updated for the current request
            $_SESSION['rapo_state'][static::class][$key] = $value;
        }
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
