<?php

namespace Rapo\Http\Middleware;

use Rapo\Http\Request;
use Rapo\Http\Response;

class VerifyCsrfToken {
    public function handle(Request $request, \Closure $next) {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $token = $request->getPost('_token') ?: $request->getHeader('X-CSRF-TOKEN');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$token || $token !== ($_SESSION['_token'] ?? '')) {
            throw new \Exception('CSRF token mismatch.', 403);
        }

        return $next($request);
    }
}
