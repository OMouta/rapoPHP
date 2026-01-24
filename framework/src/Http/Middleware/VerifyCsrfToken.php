<?php

namespace Rapo\Http\Middleware;

use Rapo\Http\Request;
use Rapo\Http\Response;

class VerifyCsrfToken {
    public function handle(Request $request, Response $response) {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        $token = $request->getPost('_token') ?: $request->getHeader('X-CSRF-TOKEN');

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$token || $token !== ($_SESSION['_token'] ?? '')) {
            $response->setStatusCode(403);
            $response->setContent('CSRF token mismatch.');
            $response->send();
            return false;
        }

        return true;
    }
}
