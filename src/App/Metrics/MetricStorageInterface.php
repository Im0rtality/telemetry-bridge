<?php

namespace App\Metrics;

use Generator;

interface MetricStorageInterface
{
    public function store(Metric $metric): void;

    /**
     * @return \Generator|Metric[]
     */
    public function all(): Generator;
}
