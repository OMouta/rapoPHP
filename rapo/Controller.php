<?php

namespace Rapo;

class Controller {
    protected $store;

    public function __construct() {
        $this->store = Store::getDefault();
    }

    public function __get($name) {
        return $this->store->get($name);
    }
}
