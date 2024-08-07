<?php

namespace App\Metrics\Exporter;

use App\Metrics\Metric;
use App\Metrics\MetricExporterInterface;

class SimplePrometheusExporter implements MetricExporterInterface
{
    public function export(Metric $metric): string
    {
        $labels = $this->formatLabels($metric->labels);

        return "{$metric->name}{$labels} {$metric->value}" . PHP_EOL;
    }

    private function formatLabels(array $labels): string
    {
        if (empty($labels)) {
            return '';
        }

        $formattedLabels = [];
        foreach ($labels as $label) {
            $formattedLabels[] = "{$label->key}=\"{$label->value}\"";
        }

        return '{' . implode(',', $formattedLabels) . '}';
    }
}
