<?php declare(strict_types = 1);

namespace Statistics\Models;

use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    protected $guarded = [];

    /**
     * Get the table associated with the model.
     *
     */
    public function getTable() : string
    {
        return config('statistics.table', 'statistics');
    }
}
