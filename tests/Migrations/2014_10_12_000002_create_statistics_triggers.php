<?php declare(strict_types = 1);

use Statistics\Tests\Models\Post;
use Statistics\Tests\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() : void
    {
        User::trackAll()
            ->count()
            ->create();

        User::track()
            ->watch(Post::class, [
                'post_count'     => "(SELECT COUNT(*) FROM `posts` WHERE `user_id` = {ROW}.user_id)",
                'post_sum_likes' => "(SELECT SUM(`likes`) FROM `posts` WHERE `user_id` = {ROW}.user_id)",
            ])
            ->create();

        Post::trackAll()
            ->count()
            ->sum('likes', 'likes_sum')
            ->average('likes')
            ->minimum('likes')
            ->maximum('likes')
            ->create();
    }
};
