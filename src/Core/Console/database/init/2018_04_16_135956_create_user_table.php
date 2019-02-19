<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('user', function (Blueprint $table) {
            $table->increments('id')->unsigned()->comment("用户ID,主键自增");
            $table->string('email', 32)->comment("用户的登录邮箱")->default("");
            $table->string('headimg', 255)->comment("头像")->default("");
            $table->string('username', 32)->comment("用户名称，方便用户之间辨认")->default("");
            $table->string('nickname', 255)->comment("昵称")->default("");
            $table->bigInteger('phone')->length(20)->unsigned()->comment("手机号")->default("0");
            $table->date('birthday')->comment("生日")->nullable();
            $table->tinyInteger('sex')->length(1)->unsigned()->comment("性别:0,未设置;1,男;2,女;")->default("0");
            $table->text('desc')->comment("个人签名");
            $table->string('pay_password', 255)->comment("支付密码加密字符串")->default("");
            $table->string('salt', 100)->comment("支付密码扰乱字符串")->default("");
            $table->tinyInteger('status')->length(1)->unsigned()->comment("用户状态:0,禁止;1,正常;")->default("1");
            $table->tinyInteger('is_wechat')->length(1)->unsigned()->comment("是否绑定微信")->default("0");
            $table->tinyInteger('is_qq')->length(1)->unsigned()->comment("是否绑定qq")->default("0");
            $table->tinyInteger('is_weibo')->length(1)->unsigned()->comment("是否绑定微博")->default("0");
            $table->string('country', 20)->length(20)->comment("所在国家")->default("0");
            $table->string('province', 20)->length(20)->comment("所在省")->default("0");
            $table->string('city', 20)->length(20)->comment("所在市")->default("0");
            $table->string('area', 20)->length(20)->comment("所在区")->default("0");
            $table->bigInteger('last_login_ip')->length(20)->unsigned()->comment("用户最后一次登录的时间")->default("0");
            $table->integer('last_login_time')->length(11)->unsigned()->comment("最后登录时间")->default("0");
            $table->bigInteger('register_ip')->length(20)->unsigned()->comment("注册ip")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->comment("用户状态。0为已删除，1为正常")->default("1");
            $table->comment = '用户表';
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
        //Schema::dropIfExists('user');
    }
}
