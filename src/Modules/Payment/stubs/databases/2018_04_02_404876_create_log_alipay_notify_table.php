<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogAlipayNotifyTable extends Migration
{
    /**
     *
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('log_alipay_notify', function (Blueprint $table) {
            $table->increments('id');
            $table->string('app_id', 255)->comment("开发者的app_id")->nullable();
            $table->string('out_trade_no', 255)->comment("商户订单号")->nullable();
            $table->string('trade_no', 255)->comment("支付宝交易号")->nullable();
            $table->string('trade_status', 255)->comment("交易状态")->nullable();
            $table->string('out_biz_no', 255)->comment("商户业务号,商户业务ID，主要是退款通知中返回退款申请的流水号")->nullable();
            $table->decimal('invoice_amount', 10, 2)->comment("开票金额")->default("0.00")->nullable();
            $table->decimal('receipt_amount', 10, 2)->comment("实收金额,商家在交易中实际收到的款项，单位为元，精确到小数点后2位")->default("0.00")->nullable();
            $table->decimal('total_amount', 10, 2)->comment("订单金额")->default("0.00")->nullable();
            $table->decimal('buyer_pay_amount', 10, 2)->comment("付款金额，用户在交易中支付的金额，单位为元，精确到小数点后2位")->default("0.00")->nullable();
            $table->decimal('point_amount', 10, 2)->comment("集分宝金额,使用集分宝支付的金额，单位为元，精确到小数点后2位")->default("0.00")->nullable();
            $table->string('buyer_id', 255)->comment("买家支付宝用户号")->nullable();
            $table->string('buyer_logon_id', 255)->comment('App买家支付宝账号')->nullable();
            $table->decimal('refund_fee', 10, 2)->comment("总退款金额,退款通知中，返回总退款金额，单位为元，精确到小数点后2位")->default("0.00")->nullable();
            $table->string('subject', 255)->comment("订单标题	")->nullable();
            $table->string('body', 500)->comment("商品描述")->nullable();
            $table->string('sign_type', 255)->comment("签名类型")->nullable();
            $table->string('auth_app_id', 255)->comment("授权方的app_id")->nullable();
            $table->string('charset', 255)->comment("编码格式	")->nullable();
            $table->dateTime('notify_time')->comment("通知时间")->nullable();
            $table->string('notify_type', 255)->comment("通知类型")->nullable();
            $table->string('notify_id', 255)->comment("通知校验ID")->nullable();
            $table->dateTime('gmt_create')->comment("交易创建时间")->nullable();
            $table->dateTime('gmt_payment')->comment("交易付款时间")->nullable();
            $table->dateTime('gmt_refund')->comment("交易退款时间")->nullable();
            $table->dateTime('gmt_close')->comment("交易结束时间")->nullable();
            $table->string('version', 255)->comment("接口版本	")->nullable();
            $table->string('sign', 255)->comment("签名")->nullable();
            $table->string('fund_bill_list', 600)->comment("支付金额信息")->nullable();
            $table->string('voucher_detail_list', 600)->comment("优惠券信息")->nullable();
            $table->string('seller_id', 255)->comment("卖家支付宝用户号")->nullable();
            $table->string('seller_email', 255)->comment("卖家支付宝邮箱")->nullable();
            $table->string('passback_params', 1000)->comment("回传参数")->nullable();
            $table->string('raw_data', 5000)->comment("原始数据")->nullable();
            $table->tinyInteger('deal_status')->length(1)->comment("处理状态:0,失败;1,成功;")->default("0")->nullable();
            $table->string('error_msg', 255)->comment("处理错误信息")->nullable();
            $table->bigInteger('created_at')->length(15)->nullable();
            $table->bigInteger('updated_at')->length(15)->nullable();
            $table->comment = '日志-支付宝回调';
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
        //Schema::dropIfExists('log_alipay_notify');
    }
}
