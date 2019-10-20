<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChannelFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_fees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('channel_id');
            $table->dateTime('effective_from');
            $table->decimal('fee', 8, 2)->default(100);
            $table->unsignedBigInteger('added_by');
            $table->timestamps();
            $table->softDeletes();
        });
        /*Schema::connection('mysql_r')->create('channel_fees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('channel_id');
            $table->dateTime('effective_from');
            $table->decimal('fee', 8, 2)->default(100);
            $table->unsignedBigInteger('added_by');
            $table->timestamps();
        });*/
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_fees');
        /*Schema::connection('mysql_r')->dropIfExists('channel_fees');*/
    }
}
