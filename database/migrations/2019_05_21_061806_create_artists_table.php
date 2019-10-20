<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArtistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('membership_number')->nullable();
            $table->integer('type'); // 1 - singer, 2 - music_director, 3 - song_writer, 4 - producer, 5 - unknown
            $table->integer('status')->default(1); // 1 - active, 2 - consented_member, 3 - non_member, 4 - deceased_active, 5 - deceased_consented_member, 6 - deceased_non_member
            $table->unsignedBigInteger('added_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        /*Schema::connection('mysql_r')->create('artists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('membership_number')->nullable();
            $table->integer('type'); // 1 - singer, 2 - music_director, 3 - song_writer, 4 - producer, 5 - unknown
            $table->integer('status')->default(1); // 1 - active, 2 - consented_member, 3 - non_member, 4 - deceased_active, 5 - deceased_consented_member, 6 - deceased_non_member
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
        Schema::dropIfExists('artists');
//        Schema::connection('mysql_r')->dropIfExists('artists');
    }
}
