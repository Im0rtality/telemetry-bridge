<?php

namespace App\Controller;

use App\Metrics\MetricExporterInterface;
use App\Metrics\MetricStorageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\Response;

class MetricsController
{
    public function __construct(
        private readonly MetricStorageInterface $storage,
        private readonly MetricExporterInterface $exporter,
    ) {
    }

    public function prometheus(RequestInterface $request): ResponseInterface
    {
        $response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            ''
        );

        foreach ($this->storage->all() as $metric) {
            $response->getBody()->write($this->exporter->export($metric));
        }

        return $response;
    }
}
