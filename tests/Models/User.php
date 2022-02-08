<?php declare(strict_types = 1);

namespace Statistics\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Statistics\InteractsWithStatistics;

class User extends Model
{
    use InteractsWithStatistics;

    protected $guarded = [];
}
