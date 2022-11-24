<?php

namespace Igniter\System\Database\Migrations;

use Illuminate\Database\Migrations\Migration;

/**
 * Do not run to avoid conflicts with Laravel's queue table.
 */
return new class extends Migration {
    public function up()
    {
//        if (!Schema::hasTable('failed_jobs'))
//            return;
//
//        Schema::table('failed_jobs', function (Blueprint $table) {
//            $table->string('uuid')->after('id')->nullable()->unique();
//        });
//
//        DB::table('failed_jobs')->whereNull('uuid')->cursor()->each(function ($job) {
//            DB::table('failed_jobs')
//                ->where('id', $job->id)
//                ->update(['uuid' => (string)Str::uuid()]);
//        });
    }

    public function down()
    {
        //
    }
};
