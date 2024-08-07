<?php

namespace App;

use App\Controller\MetricsController;
use App\Controller\StatusController;
use App\Metrics\Exporter\SimplePrometheusExporter;
use App\Metrics\MetricExporterInterface;
use App\Metrics\MetricStorageInterface;
use App\Metrics\Source\MqttSource;
use App\Metrics\Storage\ExpiringMemoryStorage;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Router;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\Message\Response;
use Throwable;

class Kernel
{
    private MetricStorageInterface $storage;

    private MetricExporterInterface $exporter;

    private MqttSource $source;

    private Router $router;

    public function __construct(
        private readonly LoopInterface $loop,
        private readonly Logger $logger,
        string $mqttConnection
    ) {
        $this->storage = new ExpiringMemoryStorage(5);
        $this->exporter = new SimplePrometheusExporter();

        $url = parse_url($mqttConnection); // tcp://user:password@hostname:port/topic
        $this->source = new MqttSource(
            $url['host'],
            $url['port'],
            $url['user'] ?? null,
            $url['pass'] ?? null,
            $url['path'],
            $this->logger->withName('mqtt_source'),
            $this->storage
        );

        $this->router = new Router();
    }

    public function boot(): void
    {
        $this->logger->debug('Booting application');

        $this->configureRouting();
        $this->source->connect();

        $this->loop->addPeriodicTimer(0.1, $this->onLoop(...));
    }

    private function configureRouting(): void
    {
        $metricsController = new MetricsController($this->storage, $this->exporter);
        $statusController = new StatusController();

        $this->router->map('GET', '/metrics/prometheus', $metricsController->prometheus(...));
        $this->router->map('GET', '/_healthz', $statusController->healthz(...));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->router->dispatch($request);
        } catch (NotFoundException $e) {
            return new Response(
                404,
                ['Content-Type' => 'text/plain'],
                'Not Found'
            );
        } catch (Throwable $e) {
            $this->logger->error('Error: ' . $e->getMessage(), ['exception' => $e]);
            return new Response(
                500,
                ['Content-Type' => 'text/plain'],
                'Internal Server Error'
            );
        }
    }

    public function shutdown(): void
    {
        $this->logger->info('Shutting down application');
        $this->source->disconnect();
    }

    private function onLoop(): void
    {
        $this->source->onLoop();
    }
}
