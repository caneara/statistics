<!-- Screenshot -->
<p align="center">
    <img src="resources/wallpaper.jpg" alt="Wallpaper">
</p>

<!-- Badges -->
<p align="center">
  <img src="resources/version.svg" alt="Version">
  <img src="resources/license.svg" alt="License">
</p>

# Statistics

This package enables a Laravel application to maintain statistics of aggregated database records. It serves as a companion package to (and relies upon) [triggers](https://github.com/mattkingshott/triggers).

## Who is this for?

If you're running queries that are slow because they need to perform aggregations (`COUNT`, `SUM`, `MIN`, `MAX` or `AVG`) across many records, then you might get some value from this package. A common scenario where this takes place, is on a dashboard that displays lots of statistics e.g.

```sql
SELECT
    (SELECT COUNT(*) FROM `articles`) AS 'articles',
    (SELECT COUNT(*) FROM `projects`) AS 'projects',
    (SELECT COUNT(*) FROM `tasks`) AS 'tasks'
```

By contrast, you can configure the package to automatically maintain statistics in the background. So, instead of a slow query (like the above example), you can instead do this:

```sql
SELECT
    `table`, `values`
FROM
    `statistics`
WHERE
    `table`
IN
    ('articles', 'projects', 'tasks')
```

Or, better still, use the Eloquent model to query the data:

```php
use Statistics\Models\Statistic;

$stats = Statistic::query()
    ->whereIn('table', ['articles', 'projects', 'tasks'])
    ->get(['table', 'values']);
```

| Table     | Values            |
| --------- | ----------------- |
| Articles  | `{ "count" : 6 }` |
| Projects  | `{ "count" : 3 }` |
| Tasks     | `{ "count" : 2 }` |

This becomes even more powerful when using joins for individual rows:

```php
$users = User::query()
    ->join('statistics', function ($join) {
        $join->on('users.id', '=', 'statistics.id')
                ->where('statistics.table', 'users');
    })
    ->get(['users.name', DB::raw('`values`->"$.post_count" AS `posts`')])
    ->orderByRaw('`values`->"$.post_count" DESC')
```

| Name  | Posts |
| ----- | ----- |
| John  | 6     |
| Fred  | 4     |
| Dave  | 1     |

## How does it work?

The package will automatically register and migrate a `statistics` table to your database. This table then serves as a repository for aggregated values. You can then easily join records to this table to get the associated metrics you need.

The aggregated values are maintained using database triggers, which will automatically fire after a record is inserted, updated or deleted.

## Installation

Pull in the package using Composer:

```bash
composer require mattkingshott/statistics
```

## Configuration

The package includes a configuration file that allows you to change the name of the database table that contains the aggregated values (the default is 'statistics'). If you want to change it, publish the configuration file using Artisan:

```bash
php artisan vendor:publish
```

## Usage

The package allows you to:

1. Maintain statistics for all the records in a table.
2. Maintain statistics for individual rows in a table.
3. Maintain statistics for individual AND all records in a table.

Before proceeding further, it is important to remember that database triggers (which the package relies on) can only be added to a table after it has been created. Therefore, you should ensure that any referenced tables have first been added via `Schema::create` within your migrations e.g.

```php
// At this point, adding statistics that rely on the 'users' table will
// throw a SQL error as the table has not yet been added to the database.

Schema::create('users', function(Blueprint $table) {
    // ...
});

// At this point, adding statistics that rely on the 'users' table will not
// throw a SQL error as the table has been added to the database.
```

### Configuring models

To begin, add the `InteractsWithStatistics` trait to any `Model` class that you want to maintain statistics for e.g.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Statistics\Traits\InteractsWithStatistics;

class Article extends Model
{
    use InteractsWithStatistics;
}
```

### Tracking all records

To maintain statistics across the entire table (e.g. how many rows there are), call the static `trackAll` method on the `Model`.

```php
Article::trackAll();
```

Next, call one or more of the available aggregation methods:

```php
Article::trackAll()
    ->count()           // Count all records
    ->sum('likes')      // Get the sum of all records using the 'likes' column
    ->average('likes')  // Get the average value from the 'likes' column
    ->minimum('likes')  // Get the smallest value in the 'likes' column
    ->maximum('likes'); // Get the largest value in the 'likes' column
```

You can call an aggregation method more than once if you need to maintain statistics on multiple columns. Simply supply a custom name to differentiate them:

```php
Article::trackAll()
    ->count()
    ->sum('likes', 'sum_likes')
    ->sum('views', 'sum_views');
```

Finally, call the `create` method to install the triggers.

```php
Article::trackAll()
    ->count()
    ->create();
```

#### Example

Here's a simple example within a database migration:

```php
class CreateArticlesTable extends Migration
{
    public function up() : void
    {
        Schema::create('articles', function(Blueprint $table) {
            $table->unsignedTinyInteger('id');
            $table->string('title');
        });

        Article::trackAll()
            ->count()
            ->create();
    }
}
```

### Tracking individual rows

In addition to maintaining statistics on a whole table, you can also maintain statistics for individual rows. This can be useful when tracking related records e.g. the count of 'articles', 'projects' and 'tasks' belonging to a 'user'.

To begin, call the static `track` method on the `Model`:

```php
User::track();
```

Next, we need to add a watcher for each of the related tables. We do this by calling the `watch` method and supplying the related `Model`:

```php
User::track()
    ->watch(Task::class);
```

The package will guess the foreign key on `Task` by combining the main model (`User`) and `_id`. However, you can override this by supplying your own foreign key:

```php
User::track()
    ->watch(Task::class, 'author_id');
```

Next, we need to provide an array of SQL statements that the trigger should execute in order to maintain one or more statistics:

```php
User::track()
    ->watch(Task::class, [
        'task_count' => "(SELECT COUNT(*) FROM `tasks` WHERE `user_id` = {ROW}.user_id)",
    ]);

// Or when supplying a custom foreign key...

User::track()
    ->watch(Task::class, 'author_id', [
        'task_count' => "(SELECT COUNT(*) FROM `tasks` WHERE `author_id` = {ROW}.author_id)",
    ]);
```

Notice the use of `{ROW}` in the SQL statement. You can use this placeholder to access the current row within the trigger. `{ROW}` will be automatically replaced with `OLD` or `NEW` depending on the event type.

You are free to maintain more than one statistic for a related table if required e.g.

```php
User::track()
    ->watch(Task::class, [
        'task_count'        => "(SELECT COUNT(*) FROM `tasks` WHERE `user_id` = {ROW}.user_id)",
        'task_max_priority' => "(SELECT MAX(`priority`) FROM `tasks` WHERE `user_id` = {ROW}.user_id)",
    ]);
```

Next, repeat the process for any further watchers that you need e.g.

```php
User::track()
    ->watch(Task::class, [
        'task_count'        => "(SELECT COUNT(*) FROM `tasks` WHERE `user_id` = {ROW}.user_id)",
        'task_max_priority' => "(SELECT MAX(`priority`) FROM `tasks` WHERE `user_id` = {ROW}.user_id)",
    ])
    ->watch(Project::class, [
        'project_count' => "(SELECT COUNT(*) FROM `projects` WHERE `user_id` = {ROW}.user_id)",
    ])
    ->watch(Article::class, [
        'article_count' => "(SELECT COUNT(*) FROM `articles` WHERE `user_id` = {ROW}.user_id)",
    ]);
```

Finally, call the `create` method to install the triggers.

```php
User::track()
    ->watch(Task::class, [
        'task_count' => "(SELECT COUNT(*) FROM `tasks` WHERE `user_id` = {ROW}.user_id)",
    ])
    ->create();
```

#### Example

Here's a simple example within a database migration:

```php
return new class extends Migration
{
    public function up() : void
    {
        User::track()
            ->watch(Task::class, [
                'task_count'        => "(SELECT COUNT(*) FROM `tasks` WHERE `user_id` = {ROW}.user_id)",
                'task_max_priority' => "(SELECT MAX(`priority`) FROM `tasks` WHERE `user_id` = {ROW}.user_id)",
            ])
            ->watch(Project::class, [
                'project_count' => "(SELECT COUNT(*) FROM `projects` WHERE `user_id` = {ROW}.user_id)",
            ])
            ->watch(Article::class, [
                'article_count' => "(SELECT COUNT(*) FROM `articles` WHERE `user_id` = {ROW}.user_id)",
            ])
            ->create();
    }
};
```

## Contributing

Thank you for considering a contribution to the package. You are welcome to submit a PR containing improvements, however if they are substantial in nature, please also be sure to include a test or tests.

## Support the project

If you'd like to support the development of the package, then please consider [sponsoring me](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YBEHLHPF3GUVY&source=url). Thanks so much!

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
