<?php

namespace Rapo\Testing;

class TestResponse {
    protected $content;

    public function __construct($content) {
        $this->content = $content;
    }

    public function assertSee($text) {
        if (strpos($this->content, $text) === false) {
            throw new \Exception("Failed asserting that response contains: $text");
        }
        return $this;
    }

    public function assertStatus($code) {
        if (http_response_code() !== $code) {
            throw new \Exception("Failed asserting status code $code, got " . http_response_code());
        }
        return $this;
    }

    public function getContent() {
        return $this->content;
    }
}
