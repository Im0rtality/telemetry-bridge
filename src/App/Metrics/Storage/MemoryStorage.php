<?php

namespace App\Metrics\Storage;

use App\Metrics\Metric;
use App\Metrics\MetricStorageInterface;
use Generator;

class MemoryStorage implements MetricStorageInterface
{
    /** @var array<string, Metric> */
    private array $metrics = [];

    public function store(Metric $metric): void
    {
        $this->metrics[$this->makeKey($metric->name, $metric->labels)] = $metric;
    }

    private function makeKey(string $name, array $labels): string
    {
        $key = $name . ':';
        foreach ($labels as $label) {
            $key .= $label->key . '=' . $label->value;
        }
        return $key;
    }

    /**
     * @inheritDoc
     */
    public function all(): Generator
    {
        foreach ($this->metrics as $metric) {
            yield $metric;
        }
    }
}
