<?php declare(strict_types=1);

namespace Statistics\Traits;

use Statistics\Trackers\RowTracker;
use Statistics\Trackers\TableTracker;

trait InteractsWithStatistics
{
    /**
     * Track individual model records.
     *
     */
    public static function track() : RowTracker
    {
        $model = new static();

        return RowTracker::make($model->getTable(), $model->getKeyName());
    }

    /**
     * Track all model records.
     *
     */
    public static function trackAll() : TableTracker
    {
        $model = new static();

        return TableTracker::make($model->getTable());
    }
}
