<?php

namespace Rapo\Http\Middleware;

use Rapo\Auth;
use Rapo\Http\Request;
use Rapo\Http\Response;

class Authenticate {
    public function handle(Request $request, \Closure $next) {
        if (!Auth::check()) {
            return redirect('/login');
        }
        return $next($request);
    }
}
