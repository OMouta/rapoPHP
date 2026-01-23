<?php

namespace Rapo;

class Head {
    protected static $instance;
    protected $title = 'RapoPHP';
    protected $tags = [];

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }

    public function addTag($tag) {
        $this->tags[] = $tag;
    }

    public function renderTags() {
        return implode("\n", $this->tags);
    }

    public static function getInstance() {
        if (!static::$instance) {
            static::$instance = new self();
        }
        return static::$instance;
    }
}
