<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRulesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rules', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name');
			$table->string('type');
			$table->integer('ruleset_id');
			$table->string('variable1');
			$table->string('operator');
			$table->string('variable2');
			$table->string('description')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
        Schema::create('rulesets', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name');
			$table->string('type');
			$table->string('description')->nullable();
			$table->string('match')->default("ALL");
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
