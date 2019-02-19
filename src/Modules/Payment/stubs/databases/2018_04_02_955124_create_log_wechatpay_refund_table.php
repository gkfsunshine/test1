<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogWechatpayRefundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('log_wechatpay_refund', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->length(11)->unsigned()->comment("用户id")->default("0");
            $table->tinyInteger('type')->length(1)->unsigned()->comment("支付类型:1,饮水机押金退款;")->default("1");
            $table->string('out_trade_no', 50)->comment("支付的订单号(需要退款的支付订单号)")->default("");
            $table->string('wechat_transaction_id', 50)->comment("微信订单号(需要退款的微信订单号)")->default("");
            $table->string('out_refund_no', 50)->comment("退款单号")->default("");
            $table->integer('total_fee')->length(11)->unsigned()->comment("订单总金额,单位:分")->default("0");
            $table->integer('refund_fee')->length(11)->unsigned()->comment("退款总金额,单位:分")->default("0");
            $table->tinyInteger('status')->length(1)->unsigned()->comment("状态:0,未处理;1,已回调处理;")->default("0");
            $table->integer('dealed_at')->length(11)->unsigned()->comment("处理时间")->default("0");
            $table->bigInteger('transaction_id')->length(20)->unsigned()->comment("本平台交易流水号")->default("0");
            $table->string('refund_desc', 255)->comment("退款原因")->default("");
            $table->integer('rent_order_refund_id')->length(11)->unsigned()->comment("租赁订单退款id")->default("0");
            $table->integer('rent_order_refund_item_id')->length(11)->unsigned()->comment("租赁订单退款项id")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->comment = '微信支付退款日志';
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
        //Schema::dropIfExists('log_wechatpay_refund');
    }
}
