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
            $table->string('content')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
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

        Schema::create('default_test', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('string_not_null_no_default');
            $table->string('string_not_null_default')->default('default string');
            $table->string('string_null_no_default')->nullable();
            $table->string('string_null_default')->nullable()->default('default string');
            $table->integer('integer_not_null_no_default');
            $table->integer('integer_not_null_default')->default(5);
            $table->integer('integer_null_no_default')->nullable();
            $table->integer('integer_null_default')->nullable()->default(5);
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
        Schema::drop('default_test');
        Schema::drop('photos');
        Schema::drop('phones');
        Schema::drop('posts');
        Schema::drop('users');
    }
}
