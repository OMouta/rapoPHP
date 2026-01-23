<?php

return [
    function($request, $response) {
        $response->setHeader('X-Framework', 'RapoPHP');
        
        // Simple Route Protection Example
        if (strpos($request->getUri(), '/admin') === 0) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['is_logged_in'])) {
                // Redirect if not "logged in" for demo purposes
                // But for middleware, we can just stop execution or redirect
                // Let's just set a flag for now
                // Actually let's redirect
                header("Location: " . \Rapo\url('/') . "?error=Unauthorized");
                return false;
            }
        }
        return true;
    }
];
