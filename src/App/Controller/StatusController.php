<?php

namespace App\Controller;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\Response;

class StatusController
{
    public function healthz(RequestInterface $request): ResponseInterface
    {
        return new Response(
            200,
            ['Content-Type' => 'text/plain'],
            ''
        );
    }
}
