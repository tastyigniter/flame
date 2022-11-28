<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Do not run to avoid conflicts with Laravel's queue table. Since v4
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
//        Schema::create('jobs', function (Blueprint $table) {
//            $table->bigIncrements('id');
//            $table->string('queue')->index();
//            $table->longText('payload');
//            $table->unsignedTinyInteger('attempts');
//            $table->unsignedInteger('reserved_at')->nullable();
//            $table->unsignedInteger('available_at');
//            $table->unsignedInteger('created_at');
//        });
//
//        Schema::create('failed_jobs', function (Blueprint $table) {
//            $table->bigIncrements('id');
//            $table->text('connection');
//            $table->text('queue');
//            $table->longText('payload');
//            $table->longText('exception');
//            $table->timestamp('failed_at')->useCurrent();
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::dropIfExists('jobs');
//        Schema::dropIfExists('failed_jobs');
    }
};
