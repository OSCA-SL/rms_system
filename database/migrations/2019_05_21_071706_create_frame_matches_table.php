<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFrameMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('frame_matches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('timestamp');
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('song_id');
            $table->integer('score');
//            $table->timestamps();

            $table->index(['channel_id', 'timestamp']);
        });
        /*Schema::connection('mysql_r')->create('frame_matches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('timestamp');
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('song_id');
            $table->integer('score');
//            $table->timestamps();

            $table->index(['channel_id', 'timestamp']);
        });*/
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('frame_matches');
//        Schema::connection('mysql_r')->dropIfExists('frame_matches');
    }
}
