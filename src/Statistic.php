<?php declare(strict_types = 1);

namespace Statistics;

use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    protected $guarded = [];

    protected $casts = ['values' => 'array'];

    public $timestamps = false;

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     */
    public function getIncrementing() : bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     */
    public function getKeyType() : string
    {
        return 'string';
    }

    /**
     * Get the table associated with the model.
     *
     */
    public function getTable() : string
    {
        return config('statistics.table', 'statistics');
    }
}
