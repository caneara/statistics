<?php declare(strict_types = 1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     */
    public function up() : void
    {
        Schema::create(config('statistics.table', 'statistics'), function(Blueprint $table) {
            $table->string('table', 100);
            $table->string('id')->default('');
            $table->json('values');

            $table->unique(['table', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     */
    public function down() : void
    {
        Schema::dropIfExists(config('statistics.table', 'statistics'));
    }
};
