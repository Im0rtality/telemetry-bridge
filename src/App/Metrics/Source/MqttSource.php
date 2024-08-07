<?php

namespace App\Metrics\Source;

use App\Metrics\Exporter\SimplePrometheusExporter;
use App\Metrics\Metric;
use App\Metrics\MetricExporterInterface;
use App\Metrics\MetricLabel;
use App\Metrics\MetricStorageInterface;
use generated\Metric as PBMetric;
use generated\Metric\Label as PBLabel;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Psr\Log\LoggerInterface;

class MqttSource
{
    private MqttClient $mqtt;
    private ConnectionSettings $settings;

    private MetricExporterInterface $printer;

    public function __construct(
        string $host,
        int $port,
        ?string $username,
        ?string $password,
        private readonly string $topic,
        private readonly LoggerInterface $logger,
        private readonly MetricStorageInterface $storage
    ) {
        $this->mqtt = new MqttClient($host, $port, logger: $this->logger);
        $this->settings = (new ConnectionSettings())
            ->setPassword($password)
            ->setUsername($username);
        $this->mqtt->registerConnectedEventHandler($this->onConnected(...));
        $this->mqtt->registerMessageReceivedEventHandler($this->onMessage(...));

        $this->printer = new SimplePrometheusExporter();
    }

    public function connect(): void
    {
        $this->mqtt->connect($this->settings);
    }

    public function onLoop()
    {
        static $start = microtime(true);
        $this->mqtt->loopOnce($start, false);
    }

    public function disconnect(): void
    {
        $this->mqtt->unsubscribe($this->topic);
        sleep(1);
        $this->mqtt->disconnect();
    }

    private function onConnected(MqttClient $mqtt, bool $isAutoReconnect)
    {
        $mqtt->subscribe($this->topic);
    }

    private function onMessage(MqttClient $mqtt, string $topic, string $message, int $qualityOfService, bool $retained)
    {
        $mapLabels = fn(PBLabel $pbLabel) => new MetricLabel($pbLabel->getKey(), $pbLabel->getValue());

        $pbMetric = new PBMetric();
        $pbMetric->mergeFromString($message);

        $metric = new Metric(
            $pbMetric->getName(),
            $pbMetric->getValue(),
            array_map($mapLabels, iterator_to_array($pbMetric->getLabels())),
            timestamp: time(),
        );
        $this->storage->store($metric);

        $this->logger->notice("Received metric: " . $this->printer->export($metric));
    }
}
