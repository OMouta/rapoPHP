<?php

namespace App\Api\status;

use Rapo\Http\Response;

class Route
{
    public function GET()
    {
        return Response::json([
            'status' => 'online',
            'version' => '2.0.0-modern',
            'framework' => 'RapoPHP',
            'environment' => PHP_OS,
            'timestamp' => time()
        ]);
    }

    public function POST($request)
    {
        return Response::json([
            'message' => 'Data received successfully',
            'body' => $request->all()
        ], 201);
    }
}
