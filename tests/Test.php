<?php declare(strict_types=1);

namespace Statistics\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\DB;
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

        DB::table('users')->truncate();
        DB::table('posts')->truncate();
    }

    /** @test */
    public function it_creates_triggers_for_inserted_and_deleted_user_records() : void
    {
        $this->assertCount(0, DB::table('statistics')->get());

        DB::table('users')->insert(['id' => 1, 'name' => 'John Doe']);

        $this->assertCount(2, DB::table('statistics')->get());

        $statistics = DB::table('statistics')
            ->orderBy('table')
            ->orderBy('id')
            ->get();

        $this->assertEquals('', $statistics[0]->id);
        $this->assertEquals('users', $statistics[0]->table);
        $this->assertEquals(['count' => 1], json_decode($statistics[0]->values, true));

        $this->assertEquals('1', $statistics[1]->id);
        $this->assertEquals('users', $statistics[1]->table);
        $this->assertEquals(['post_sum_likes' => '', 'post_count' => ''], json_decode($statistics[1]->values, true));

        DB::table('users')->delete();

        $this->assertCount(1, DB::table('statistics')->get());

        $this->assertEquals('', DB::table('statistics')->first()->id);
        $this->assertEquals('users', DB::table('statistics')->first()->table);
        $this->assertEquals(['count' => 0], json_decode(DB::table('statistics')->first()->values, true));
    }

    /** @test */
    public function it_creates_triggers_and_statistics_for_seeded_records() : void
    {
        Builder::seed();

        $this->assertCount(2, DB::table('users')->get());
        $this->assertCount(8, DB::table('posts')->get());
        $this->assertCount(4, DB::table('statistics')->get());

        $statistics = DB::table('statistics')
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

        $this->assertEquals('', $statistics[0]->id);
        $this->assertEquals('posts', $statistics[0]->table);
        $this->assertEquals($expected, json_decode($statistics[0]->values, true));

        $this->assertEquals('', $statistics[1]->id);
        $this->assertEquals('users', $statistics[1]->table);
        $this->assertEquals(['count' => 2], json_decode($statistics[1]->values, true));

        $this->assertEquals('1', $statistics[2]->id);
        $this->assertEquals('users', $statistics[2]->table);
        $this->assertEquals(['post_sum_likes' => 8, 'post_count' => 4], json_decode($statistics[2]->values, true));

        $this->assertEquals('2', $statistics[3]->id);
        $this->assertEquals('users', $statistics[3]->table);
        $this->assertEquals(['post_sum_likes' => 14, 'post_count' => 4], json_decode($statistics[3]->values, true));
    }
}
