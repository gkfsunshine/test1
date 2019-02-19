<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogWechatpayPayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('log_wechatpay_pay', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->length(11)->unsigned()->comment("用户id")->default("0");
            $table->tinyInteger('type')->length(1)->unsigned()->comment("支付类型:1,充值余额;2,企业充值余额;")->default("1");
            $table->string('out_trade_no', 50)->comment("订单号")->default("");
            $table->integer('total_amount')->length(11)->unsigned()->comment("总价,单位:分")->default("0");
            $table->string('passback_params', 5000)->comment("定义返回的字符串")->default("");
            $table->tinyInteger('status')->length(1)->unsigned()->comment("状态:0,未回调;1,已回调处理;")->default("0");
            $table->bigInteger('transaction_id')->length(20)->unsigned()->comment("交易流水号")->default("0");
            $table->bigInteger('dealed_at')->length(15)->unsigned()->comment("处理时间")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->comment = '微信支付支付日志';
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
        //Schema::dropIfExists('log_wechatpay_pay');
    }
}
