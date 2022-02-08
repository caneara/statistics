<?php declare(strict_types=1);

namespace Statistics;

use Triggers\Trigger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class Tracker
{
    /**
     * The list of aggregates to track.
     *
     */
    public Collection $aggregates;

    /**
     * The table name.
     *
     */
    public string $table;

    /**
     * Constructor.
     *
     */
    public function __construct(string $table)
    {
        $this->table = $table;

        $this->aggregates = collect();
    }

    /**
     * Monitor the average of the given column across the table records.
     *
     */
    public function average(string $column, string $key = 'average') : static
    {
        $this->aggregates->push([
            'key' => $key,
            'sql' => "AVG(`{$column}`)",
        ]);

        return $this;
    }

    /**
     * Monitor the total number of table records.
     *
     */
    public function count(string $key = 'count') : static
    {
        $this->aggregates->push([
            'key' => $key,
            'sql' => 'COUNT(*)',
        ]);

        return $this;
    }

    /**
     * Generate the triggers for the aggregates.
     *
     */
    public function create() : void
    {
        $items = $this->aggregates->map(function($item) {
            return "'{$item['key']}', {$item['sql']}";
        });

        $placeholders = [
            '{TABLE}'    => $this->table,
            '{TRACKERS}' => config('statistics.table', 'statistics'),
            '{ITEMS}'    => $items->implode(', '),
        ];

        $statement = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            File::get(__DIR__ . '/../stubs/trigger.stub')
        );

        Statistic::create([
            'table'  => $this->table,
            'values' => $this->aggregates->mapWithKeys(fn($item) => [$item['key'] => 0])->toArray(),
        ]);

        Trigger::table($this->table)->key('statistics')->afterDelete(fn() => $statement);
        Trigger::table($this->table)->key('statistics')->afterInsert(fn() => $statement);
        Trigger::table($this->table)->key('statistics')->afterUpdate(fn() => $statement);
    }

    /**
     * Generate a new aggregate tracker.
     *
     */
    public static function make(string $table) : static
    {
        return new static($table);
    }

    /**
     * Monitor the maximum value of the given column across the table records.
     *
     */
    public function maximum(string $column, string $key = 'maximum') : static
    {
        $this->aggregates->push([
            'key' => $key,
            'sql' => "MAX(`{$column}`)",
        ]);

        return $this;
    }

    /**
     * Monitor the minimum value of the given column across the table records.
     *
     */
    public function minimum(string $column, string $key = 'minimum') : static
    {
        $this->aggregates->push([
            'key' => $key,
            'sql' => "MIN(`{$column}`)",
        ]);

        return $this;
    }

    /**
     * Monitor the sum of the given column across the table records.
     *
     */
    public function sum(string $column, string $key = 'sum') : static
    {
        $this->aggregates->push([
            'key' => $key,
            'sql' => "SUM(`{$column}`)",
        ]);

        return $this;
    }
}
