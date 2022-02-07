<?php declare(strict_types = 1);

namespace Statistics\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Statistics\Traits\InteractsWithStatistics;

class Post extends Model
{
    use InteractsWithStatistics;

    protected $guarded = [];
}
