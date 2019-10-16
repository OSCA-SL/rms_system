<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('logger');
            $table->decimal('frequency', 8, 4); // in MHz
            $table->unsignedBigInteger('contact_user');
            $table->unsignedBigInteger('added_by');
            $table->string('details')->nullable();

            /*Used for fetching from the Radio Loging device*/
            $table->dateTime('last_fetch_at')->nullable();
            $table->dateTime('aired_time')->nullable();
            $table->unsignedTinyInteger('fetched_day')->nullable();
            $table->unsignedTinyInteger('fetched_hour')->nullable();
            $table->unsignedTinyInteger('fetched_minute')->nullable();
            $table->tinyInteger('fetch_status')->default(0);
            /*
             * 0 - first clip failed
             * 1 - first clip ok
             * 2 - second clip failed
             * 3 - second clip ok
             * 4 - merging failed
             * 5 - merging ok
             * 6 - Match Request Failed
             * 7 - Match Request Ok
             * */
            /*$table->string('first_clip')->nullable();
            $table->string('second_clip')->nullable();*/
            $table->string('clip_path')->nullable();

            /*Used only when matching with songs. Used to keep track of the gaps*/
            $table->bigInteger('song_id')->nullable();
            $table->bigInteger('match_id')->nullable();
            $table->tinyInteger('gaps')->nullable();
            /*Keep track of gaps*/

            $table->timestamps();
            $table->softDeletes();
        });
        /*Schema::connection('mysql_r')->create('channels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('logger');
            $table->decimal('frequency', 8, 4); // in MHz
            $table->unsignedBigInteger('contact_user');
            $table->unsignedBigInteger('added_by');
            $table->string('details')->nullable();
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
        Schema::dropIfExists('channels');
//        Schema::connection('mysql_r')->dropIfExists('channels');
    }
}
