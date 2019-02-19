<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogSmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('log_sms', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->length(11)->default("0");
            $table->integer('admin_id')->length(11)->default("0");
            $table->bigInteger('phone')->length(20)->comment("手机号码");
            $table->text('content')->comment("内容");
            $table->tinyInteger('type')->length(1)->comment("短信类型:1,注册 ;2,登录;3,修改密码;4,更换手机号;5,忘记密码; 6,更改支付密码; 7,绑定手机;")->default("1");
            $table->tinyInteger('send_result')->length(1)->comment("是否发送成功")->default("1");
            $table->string('error_msg', 5000)->comment("错误信息")->default("");
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->default("1");
            $table->comment = '短信日志';
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
        //Schema::dropIfExists('log_sms');
    }
}
