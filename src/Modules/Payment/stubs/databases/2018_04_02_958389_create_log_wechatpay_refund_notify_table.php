<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogWechatpayRefundNotifyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('log_wechatpay_refund_notify', function (Blueprint $table) {
            $table->increments('id');
            $table->string('return_code', 255)->comment("返回状态码")->default("");
            $table->string('return_msg', 255)->comment("返回信息")->default("");
            $table->string('result_code', 255)->comment("业务结果")->default("");
            $table->string('err_code', 255)->comment("错误代码	")->default("");
            $table->string('err_code_des', 255)->comment("错误代码描述")->default("");
            $table->string('appid', 255)->comment("公众账号ID")->default("");
            $table->string('mch_id', 255)->comment("商户号")->default("");
            $table->string('nonce_str', 255)->comment("随机字符串")->default("");
            $table->text('req_info')->comment("加密信息");
            $table->string('out_refund_no', 255)->comment("商户退款单号")->default("");
            $table->string('out_trade_no', 255)->comment("商户订单号")->default("");
            $table->string('refund_account', 255)->comment("退款资金来源")->default("");
            $table->string('refund_recv_accout', 255)->comment("退款入账账户")->default("");
            $table->integer('refund_fee')->length(11)->unsigned()->comment("申请退款金额")->default("0");
            $table->string('refund_id', 255)->comment("微信退款单号")->default("");
            $table->string('refund_request_source', 255)->comment("退款发起来源")->default("");
            $table->string('refund_status', 255)->comment("退款状态");
            $table->integer('settlement_refund_fee')->length(11)->unsigned()->comment("金额")->default("0");
            $table->integer('settlement_total_fee')->length(11)->unsigned()->comment("应结订单金额")->default("0");
            $table->string('success_time', 255)->comment("退款成功时间")->default("");
            $table->integer('total_fee')->length(11)->unsigned()->comment("订单金额")->default("0");
            $table->string('transaction_id', 255)->comment("微信支付订单号")->default("");
            $table->string('raw_data', 5000)->comment("原始数据")->default("");
            $table->tinyInteger('deal_status')->length(1)->unsigned()->comment("处理状态:0,失败;1,成功;")->default("0");
            $table->string('error_msg', 255)->comment("处理错误信息")->default("");
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->comment = '日志-微信支付退款回调';
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
        //Schema::dropIfExists('log_wechatpay_refund_notify');
    }
}
