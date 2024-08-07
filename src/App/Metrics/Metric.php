<?php

namespace App\Metrics;

final class Metric
{
    /**
     * @param MetricLabel[] $labels
     */
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly array $labels,
        public readonly int $timestamp,
    ) {
    }
}
