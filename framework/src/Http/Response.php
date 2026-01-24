<?php

namespace Rapo\Http;

class Response {
    protected $content;
    protected $statusCode = 200;
    protected $headers = [];

    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    public function getContent() {
        return $this->content;
    }

    public function setStatusCode($code) {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeader($name) {
        return $this->headers[$name] ?? null;
    }

    public static function json($data, $statusCode = 200) {
        $response = new self();
        $response->setStatusCode($statusCode);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        return $response;
    }

    public function setJson($data) {
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data));
        return $this;
    }

    public function send() {
        // Inject Debug Tools if enabled
        if (class_exists(\Rapo\Debug::class)) {
            $contentType = $this->headers['Content-Type'] ?? '';
            $isJson = stripos($contentType, 'application/json') !== false;
            
            if (!$isJson) {
                $debugHtml = \Rapo\Debug::renderDevTools();
                if ($debugHtml) {
                    if (is_string($this->content) && str_contains($this->content, '</body>')) {
                        $this->content = str_replace('</body>', $debugHtml . '</body>', $this->content);
                    } else if (is_string($this->content)) {
                        $this->content .= $debugHtml;
                    }
                }
            }
        }

        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->content;
    }
}
