<?php declare(strict_types=1);

namespace Statistics\Tests;

use Statistics\Statistic;
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
    public function it_creates_triggers_and_statistics() : void
    {
        $this->assertCount(2, Statistic::get());

        $statistics = Statistic::orderBy('table')->get();

        $expected = [
            'count'     => 0,
            'average'   => 0,
            'maximum'   => 0,
            'minimum'   => 0,
            'likes_sum' => 0,
        ];

        $this->assertEquals('posts', $statistics[0]->table);
        $this->assertEquals($expected, $statistics[0]->values);

        $this->assertEquals('users', $statistics[1]->table);
        $this->assertEquals(['count' => 0], $statistics[1]->values);
    }

    /** @test */
    public function it_updates_the_statistics_when_records_are_inserted() : void
    {
        Builder::seed();

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());
        $this->assertCount(2, Statistic::get());

        $statistics = Statistic::orderBy('table')->get();

        $expected = [
            'count'     => 8,
            'average'   => 2.75,
            'maximum'   => 4,
            'minimum'   => 1,
            'likes_sum' => 22,
        ];

        $this->assertEquals('posts', $statistics[0]->table);
        $this->assertEquals($expected, $statistics[0]->values);

        $this->assertEquals('users', $statistics[1]->table);
        $this->assertEquals(['count' => 2], $statistics[1]->values);
    }

    /** @test */
    public function it_updates_the_statistics_when_records_are_deleted() : void
    {
        Builder::seed();

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());
        $this->assertCount(2, Statistic::get());

        User::first()->delete();

        $this->assertCount(1, User::get());

        $statistics = Statistic::orderBy('table')->get();

        $expected = [
            'count'     => 8,
            'average'   => 2.75,
            'maximum'   => 4,
            'minimum'   => 1,
            'likes_sum' => 22,
        ];

        $this->assertEquals('posts', $statistics[0]->table);
        $this->assertEquals($expected, $statistics[0]->values);

        $this->assertEquals('users', $statistics[1]->table);
        $this->assertEquals(['count' => 1], $statistics[1]->values);
    }
}
