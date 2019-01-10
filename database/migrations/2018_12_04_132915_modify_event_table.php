<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyEventTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('src_type');
            $table->dropColumn('name');
            $table->dropColumn('title');
            $table->dropColumn('message');
            $table->dropColumn('timestamp');
            $table->dropColumn('dev_ip');
            $table->dropColumn('options');
            $table->string('device_name')->after('src_ip');
            $table->string('entity_type')->nullable()->after('entity_name');
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
