<?php

namespace Rapo\Http;

class RedirectResponse extends Response {
    protected $url;

    public function __construct($url) {
        $this->url = $url;
    }

    public function with($key, $value) {
        (new Session())->flash($key, $value);
        return $this;
    }

    public function send() {
        header("Location: " . $this->url);
        exit;
    }
}
