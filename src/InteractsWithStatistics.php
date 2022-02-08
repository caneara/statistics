<?php declare(strict_types=1);

namespace Statistics;

trait InteractsWithStatistics
{
    /**
     * Track all model records.
     *
     */
    public static function track() : Tracker
    {
        return Tracker::make((new static())->getTable());
    }
}
