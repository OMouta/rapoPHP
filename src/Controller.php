<?php

namespace Rapo;

class Controller {
    protected $store;
    public $props = [];

    public function __construct(array $props = []) {
        $this->store = Store::getDefault();
        $this->props = $props;
    }

    public function __get($name) {
        return $this->store->get($name);
    }
}
