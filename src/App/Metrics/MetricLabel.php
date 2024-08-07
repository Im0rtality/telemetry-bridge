<?php

namespace App\Metrics;

final class MetricLabel
{
    public function __construct(
        public readonly string $key,
        public readonly string $value,
    ) {
    }
}
