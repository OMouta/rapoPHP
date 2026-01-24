<?php

namespace Rapo\Http\Middleware;

use Rapo\Auth;
use Rapo\Http\Request;
use Rapo\Http\Response;

class Authenticate {
    public function handle(Request $request, Response $response) {
        if (!Auth::check()) {
            header("Location: /login");
            exit;
        }
        return true;
    }
}
