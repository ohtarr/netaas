<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
  Schema::create('states', function (Blueprint $table) {
            $table->increments('id');
			$table->string('name');
			$table->string('type');
			$table->boolean('state')->default(0);
			$table->boolean('processed')->default(0);
			$table->integer('incident_id')->nullable();
			$table->json('options')->nullable();			
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
        Schema::dropIfExists('state');
    }
}
