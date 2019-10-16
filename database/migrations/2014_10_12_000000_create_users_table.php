<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->dateTime('password_reset_at')->nullable();
            $table->date('dob')->nullable();
            $table->string('nic')->nullable()->unique();
            $table->string('mobile')->nullable();
            $table->string('land')->nullable();
            $table->string('address')->nullable();
            $table->integer('role')->default(2); // 1 - technical_admin 2 - admin, 3 - artist, 4 - client
            $table->unsignedBigInteger('added_by')->nullable();
            $table->string('comments')->nullable();
            $table->string('image')->default('/images/user.png');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
//        Schema::connection('mysql_r')->dropIfExists('users');
    }
}
