<?php

namespace App\Metrics\Storage;

use Generator;

class ExpiringMemoryStorage extends MemoryStorage
{
    public function __construct(private readonly int $expireSeconds)
    {
    }

    /**
     * @inheritDoc
     */
    public function all(): Generator
    {
        $now = time();
        foreach (parent::all() as $metric) {
            if ($now - $metric->timestamp < $this->expireSeconds) {
                yield $metric;
            }
        }
    }
}
