<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFingerprintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fingerprints', function (Blueprint $table) {
//            $table->bigIncrements('id');
//            $table->timestamps();
            $table->unsignedBigInteger('hash_key');
            $table->unsignedBigInteger('hash_value');
            $table->unsignedBigInteger('song_id');

            $table->primary(['hash_key', 'hash_value', 'song_id']);
        });
        /*Schema::connection('mysql_r')->create('fingerprints', function (Blueprint $table) {
//            $table->bigIncrements('id');
//            $table->timestamps();
            $table->unsignedBigInteger('hash_key');
            $table->unsignedBigInteger('hash_value');
            $table->unsignedBigInteger('song_id')->nullable();

            $table->primary(['hash_key', 'hash_value']);
        });*/
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fingerprints');
//        Schema::connection('mysql_r')->dropIfExists('fingerprints');
    }
}
