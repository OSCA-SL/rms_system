<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSongsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('remote_file_path')->nullable();
            $table->date('released_at')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('details')->nullable();
            $table->tinyInteger('hash_status')->default(0); //
//            0 - upload failed, 1 - uploaded, 2 - hash failed, 3 - hash success
            //0 - pending, 1 - success, 2 - failed
//            $table->integer('status')->default(1); // 1 - active, 2 - approved (active), 3 - pending, 4 - rejected
            $table->timestamps();
            $table->softDeletes();
        });
        /*Schema::connection('mysql_r')->create('songs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('remote_file_path')->nullable();
            $table->date('released_at')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('details')->nullable();
//            $table->integer('status')->default(1); // 1 - active, 2 - approved (active), 3 - pending, 4 - rejected
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
        Schema::dropIfExists('songs');
//        Schema::connection('mysql_r')->dropIfExists('songs');
    }
}
