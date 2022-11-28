<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Do not run to avoid conflicts with Laravel's session table. Since v4
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
//        Schema::create('sessions', function (Blueprint $table) {
//            $table->string('id')->unique();
//            $table->text('payload')->nullable();
//            $table->integer('last_activity')->nullable();
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::dropIfExists('sessions');
    }
};
