<?php

namespace Rapo;

class Mail {
    protected $transport;
    protected $from;

    public function __construct() {
        // Simple PHP mail transport by default
        // Could be extended to use SMTP/Symfony Mailer later
    }

    public function setFrom($email, $name = null) {
        $this->from = $name ? "$name <$email>" : $email;
        return $this;
    }

    /**
     * Send an email using a Rapo Component as the template
     */
    public function send($to, $subject, $componentClass, array $props = []) {
        if (!class_exists($componentClass)) {
            throw new \Exception("Email component class $componentClass not found");
        }

        $component = new $componentClass($props);
        $html = $component->render();

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'X-Mailer: RapoPHP'
        ];

        if ($this->from) {
            $headers[] = 'From: ' . $this->from;
        }

        // In a real app, you might want to use a proper library here.
        // For RapoPHP core, we'll keep it simple and extensible.
        return mail($to, $subject, $html, implode("\r\n", $headers));
    }
}
