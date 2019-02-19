<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAuthOauthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('user_auth_oauth', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->length(11)->default("0");
            $table->string('nickname', 255)->comment("授权名称")->default("");
            $table->string('headimg', 255)->comment("授权头像")->default("");
            $table->tinyInteger('oauth_type')->length(1)->unsigned()->comment("OAuth类型:1,微信;2,qq;3,微博;")->default("1");
            $table->string('id1', 255)->comment("授权id:对应微信公众号的openid")->default("");
            $table->string('id2', 255)->comment("授权id2:对应微信的unionid_id")->default("");
            $table->string('id3', 255)->comment("授权id3:对应微信APP的openid")->default("");
            $table->string('id4', 255)->comment("授权id4:对应微信小程序的openid")->default("");
            $table->string('access_token', 255)->default("");
            $table->string('refresh_token', 255)->default("");
            $table->integer('expires_time')->length(11)->unsigned()->default("0");
            $table->mediumText('info')->comment("授权信息");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->comment = '用户OAuth表';
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
        //Schema::dropIfExists('user_auth_oauth');
    }
}
