<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
			
			$table->ipAddress('src_ip');
			$table->string('src_type');

			$table->string('name');
			$table->string('type');
			$table->boolean('resolved');
			$table->string('title')->nullable();
			$table->string('message')->nullable();
			$table->string('timestamp')->nullable();
			$table->ipAddress('dev_ip')->nullable();

			$table->string('entity_name')->nullable();
			$table->string('entity_desc')->nullable();

			$table->boolean('processed')->default(0);
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
        Schema::dropIfExists('events');
    }
}
