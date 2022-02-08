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

This package enables a Laravel application to maintain aggregated statistics for database tables. It serves as a companion package to (and relies upon) [triggers](https://github.com/mattkingshott/triggers).

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

The package will automatically register and migrate a `statistics` table to your database. This table then serves as a repository for aggregated values. The values are maintained using database triggers, which will automatically fire after a record is inserted, updated or deleted.

> Before proceeding further, it is important to remember that database triggers (which the package relies on) can only be added to a table after it has been created. In other words, don't try to create statistics for a table before it has been created by `Schema::create` (this will become clearer in the examples below).

To begin, add the `InteractsWithStatistics` trait to any `Model` class that you want to maintain statistics for e.g.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Statistics\InteractsWithStatistics;

class Article extends Model
{
    use InteractsWithStatistics;
}
```

Next, call the static `track` method on the `Model`.

```php
Article::track();
```

Next, call one or more of the available aggregation methods:

```php
Article::track()
    ->count()           // Count all records
    ->sum('likes')      // Get the sum of all records using the 'likes' column
    ->average('likes')  // Get the average value from the 'likes' column
    ->minimum('likes')  // Get the smallest value in the 'likes' column
    ->maximum('likes'); // Get the largest value in the 'likes' column
```

You can call an aggregation method more than once if you need to maintain statistics on multiple columns. Simply supply a custom name to differentiate them:

```php
Article::track()
    ->count()
    ->sum('likes', 'sum_likes')
    ->sum('views', 'sum_views');
```

Finally, call the `create` method to install the triggers.

```php
Article::track()
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

        Article::track()
            ->count()
            ->create();
    }
}
```

## Contributing

Thank you for considering a contribution to the package. You are welcome to submit a PR containing improvements, however if they are substantial in nature, please also be sure to include a test or tests.

## Support the project

If you'd like to support the development of the package, then please consider [sponsoring me](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YBEHLHPF3GUVY&source=url). Thanks so much!

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
