<?php

namespace Rapo\Http\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class Validate {
    public function __construct(public array $rules) {}
}
