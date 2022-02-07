<?php declare(strict_types=1);

namespace Statistics\Trackers;

use Triggers\Trigger;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class RowTracker
{
    /**
     * The table primary key.
     *
     */
    public string $id;

    /**
     * The table name.
     *
     */
    public string $table;

    /**
     * The list of watchers.
     *
     */
    public Collection $watchers;

    /**
     * Constructor.
     *
     */
    public function __construct(string $table, string $id)
    {
        $this->id    = $id;
        $this->table = $table;

        $this->watchers = collect();
    }

    /**
     * Install the 'delete' trigger for the main table.
     *
     */
    protected function attachDeleteTrigger() : void
    {
        $placeholders = [
            '{TRACKERS}' => config('statistics.table', 'statistics'),
            '{TABLE}'    => $this->table,
            '{ID}'       => $this->id,
        ];

        $statement = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            File::get(__DIR__ . '/../../stubs/delete.stub')
        );


        Trigger::table($this->table)->afterDelete(fn() => $statement);
    }

    /**
     * Install the 'insert' trigger for the main table.
     *
     */
    protected function attachInsertTrigger() : void
    {
        $items = $this->watchers
            ->map(fn($item) => $item['aggregates']->keys())
            ->flatten()
            ->map(fn($item) => "'{$item}', ''");

        $placeholders = [
            '{TRACKERS}' => config('statistics.table', 'statistics'),
            '{TABLE}'    => $this->table,
            '{ID}'       => $this->id,
            '{ITEMS}'    => $items->implode(', '),
        ];

        $statement = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            File::get(__DIR__ . '/../../stubs/create.stub')
        );

        Trigger::table($this->table)->afterInsert(fn() => $statement);
    }

    /**
     * Generate the triggers for the aggregates.
     *
     */
    public function create() : void
    {
        $this->attachDeleteTrigger();
        $this->attachInsertTrigger();

        $this->watchers->each(function($item, $model) {
            $placeholders = [
                '{TRACKERS}'  => config('statistics.table', 'statistics'),
                '{TABLE}'     => $this->table,
                '{ID}'        => $this->id,
                '{FOREIGN}'   => $item['foreign_key'],
                '{ITEMS}'     => $item['aggregates']->map(fn($i, $k) => "'{$k}', {$i}")->implode(', '),
            ];

            $statement = str_replace(
                array_keys($placeholders),
                array_values($placeholders),
                File::get(__DIR__ . '/../../stubs/row.stub')
            );

            $on = (new $model())->getTable();

            Trigger::table($on)->key($this->table)->afterDelete(fn() => static::format('DELETE', $statement));
            Trigger::table($on)->key($this->table)->afterInsert(fn() => static::format('INSERT', $statement));
            Trigger::table($on)->key($this->table)->afterUpdate(fn() => static::format('UPDATE', $statement));
        });
    }

    /**
     * Format the given SQL statement to refer to the correct trigger keyword.
     *
     */
    protected static function format(string $event, string $sql) : string
    {
        return str_replace('{ROW}', $event === 'DELETE' ? 'OLD' : 'NEW', $sql);
    }

    /**
     * Generate a new aggregate tracker.
     *
     */
    public static function make(string $table, string $id) : static
    {
        return new static($table, $id);
    }

    /**
     * Monitor the given model's table for changes.
     *
     */
    public function watch(string $model, string | array $key, array $aggregates = []) : static
    {
        $aggregates = filled($aggregates) ? $aggregates : $key;

        $foreign = is_string($key) ? $key : Str::singular($this->table) . '_' . $this->id;

        $this->watchers = $this->watchers->merge([
            $model => [
                'foreign_key' => $foreign,
                'aggregates'  => collect($aggregates),
            ],
        ]);

        return $this;
    }
}
