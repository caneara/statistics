<?php declare(strict_types = 1);

use Statistics\Tests\Models\Post;
use Statistics\Tests\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() : void
    {
        User::track()
            ->count()
            ->create();

        Post::track()
            ->count()
            ->sum('likes', 'likes_sum')
            ->average('likes')
            ->minimum('likes')
            ->maximum('likes')
            ->create();
    }
};
