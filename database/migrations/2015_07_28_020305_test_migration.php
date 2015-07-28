<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TestMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('content');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('phones', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('number');
            $table->integer('user_id')->unsigned()->unique();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('photos', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('desc');
            $table->integer('photoable_id')->unsigned();
            $table->string('photoable_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('photos');
        Schema::drop('phones');
        Schema::drop('posts');
        Schema::drop('users');
    }
}
