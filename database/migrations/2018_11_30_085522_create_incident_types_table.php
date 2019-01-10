<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIncidentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incident_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('summary',255);
            $table->string('description',10000);
            $table->string('group');
            $table->string('ci');
            $table->integer('impact');
            $table->integer('urgency');
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
        Schema::dropIfExists('incident_types');
    }
}
