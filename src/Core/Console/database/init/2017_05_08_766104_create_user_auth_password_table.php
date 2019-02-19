<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAuthPasswordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('user_auth_password', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->length(11)->unsigned()->comment("用户id")->default("0");
            $table->string('password', 255)->comment("密码")->default("");
            $table->string('salt', 255)->comment("密码扰乱字符串")->default("");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->comment = '用户密码表';
            $table->engine = 'InnoDB';
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('user_auth_password');
    }
}
