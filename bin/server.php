<?php

use App\Kernel;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use React\Http\HttpServer;
use React\Socket\SocketServer;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new Logger('app', [
    new StreamHandler('php://stdout', Level::Info),
]);

if (!isset($_SERVER['MQTT_CONNECTION'])) {
    $logger->error('MQTT_CONNECTION environment variable is not set');
    exit(1);
}

$loop = Loop::get();
$loop->addPeriodicTimer(10, fn(TimerInterface $timer) => gc_collect_cycles(...));

$kernel = new Kernel($loop, $logger, $_SERVER['MQTT_CONNECTION']);
$kernel->boot();

$logger->info('Starting server');

$http = new HttpServer(
    $loop,
    $kernel->handle(...)
);

$http->on('error', function (Throwable $e) use (&$logger) {
    $logger->error('Error: ' . $e->getMessage(), ['exception' => $e]);
});

$shutdownFunc = function (int $signal) use (&$logger, &$socket, &$kernel) {
    $logger->info("Received signal #{$signal}, shutting down");
    $kernel->shutdown();
    $socket->pause();
};

$loop->addSignal(SIGTERM, $shutdownFunc);
$loop->addSignal(SIGINT, $shutdownFunc);

$socket = new SocketServer('0.0.0.0:8080');
$http->listen($socket);
$logger->notice('Server started, listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()));

$loop->run();
