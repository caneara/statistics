<?php declare(strict_types=1);

namespace Statistics\Tests;

use Statistics\Models\Statistic;
use Orchestra\Testbench\TestCase;
use Statistics\Tests\Models\Post;
use Statistics\Tests\Models\User;
use Statistics\Tests\World\Builder;

class Test extends TestCase
{
    /**
     * Setup the test environment.
     *
     */
    protected function setUp() : void
    {
        parent::setUp();

        Builder::create();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');

        User::truncate();
        Post::truncate();
    }

    /** @test */
    public function it_creates_triggers_for_inserted_and_deleted_user_records() : void
    {
        $this->assertCount(0, Statistic::get());

        User::insert(['id' => 1, 'name' => 'John Doe']);

        $this->assertCount(2, Statistic::get());

        $statistics = Statistic::query()
            ->orderBy('table')
            ->orderBy('id')
            ->get();

        $this->assertEquals('', $statistics[0]->getRawOriginal('id'));
        $this->assertEquals('users', $statistics[0]->table);
        $this->assertEquals(['count' => 1], json_decode($statistics[0]->values, true));

        $this->assertEquals('1', $statistics[1]->getRawOriginal('id'));
        $this->assertEquals('users', $statistics[1]->table);
        $this->assertEquals(['post_sum_likes' => '', 'post_count' => ''], json_decode($statistics[1]->values, true));

        User::query()->delete();

        $this->assertCount(1, Statistic::get());

        $this->assertEquals('', Statistic::first()->getRawOriginal('id'));
        $this->assertEquals('users', Statistic::first()->table);
        $this->assertEquals(['count' => 0], json_decode(Statistic::first()->values, true));
    }

    /** @test */
    public function it_creates_triggers_and_statistics_for_seeded_records() : void
    {
        Builder::seed();

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());
        $this->assertCount(4, Statistic::get());

        $statistics = Statistic::query()
            ->orderBy('table')
            ->orderBy('id')
            ->get();

        $expected = [
            'count'     => 8,
            'average'   => 2.75,
            'maximum'   => 4,
            'minimum'   => 1,
            'likes_sum' => 22,
        ];

        $this->assertEquals('', $statistics[0]->getRawOriginal('id'));
        $this->assertEquals('posts', $statistics[0]->table);
        $this->assertEquals($expected, json_decode($statistics[0]->values, true));

        $this->assertEquals('', $statistics[1]->getRawOriginal('id'));
        $this->assertEquals('users', $statistics[1]->table);
        $this->assertEquals(['count' => 2], json_decode($statistics[1]->values, true));

        $this->assertEquals('1', $statistics[2]->getRawOriginal('id'));
        $this->assertEquals('users', $statistics[2]->table);
        $this->assertEquals(['post_sum_likes' => 8, 'post_count' => 4], json_decode($statistics[2]->values, true));

        $this->assertEquals('2', $statistics[3]->getRawOriginal('id'));
        $this->assertEquals('users', $statistics[3]->table);
        $this->assertEquals(['post_sum_likes' => 14, 'post_count' => 4], json_decode($statistics[3]->values, true));
    }
}
