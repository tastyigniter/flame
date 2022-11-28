<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::table('menu_item_options', function (Blueprint $table) {
            $table->after('menu_id', function ($table) {
                $table->string('option_name');
            });
        });

        Schema::table('menu_item_option_values', function (Blueprint $table) {
            $table->after('menu_option_id', function ($table) {
                $table->string('name');
            });
        });
    }

    public function down()
    {
    }
};
