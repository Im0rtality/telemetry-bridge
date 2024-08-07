<?php

namespace App\Metrics;

interface MetricExporterInterface
{
    public function export(Metric $metric): string;
}
