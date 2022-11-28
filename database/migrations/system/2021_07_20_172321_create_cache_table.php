<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Do not run to avoid conflicts with Laravel's cache table. Since v4
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::create('cache', function (Blueprint $table) {
//            $table->string('key')->unique();
//            $table->mediumText('value');
//            $table->integer('expiration');
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::dropIfExists('cache');
    }
};
