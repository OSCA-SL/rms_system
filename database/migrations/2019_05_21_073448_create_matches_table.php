<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('song_id');
            $table->timestamps();
            $table->softDeletes();
        });
        /*Schema::connection('mysql_r')->create('matches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('stat');
            $table->dateTime('end');
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('song_id');
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
        Schema::dropIfExists('matches');
//        Schema::connection('mysql_r')->dropIfExists('matches');
    }
}
