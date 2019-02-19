<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogWechatpayNotifyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('log_wechatpay_notify', function (Blueprint $table) {
            $table->increments('id');
            $table->string('return_code', 255)->comment("返回状态码")->nullable();
            $table->string('return_msg', 255)->comment("返回信息")->nullable();
            $table->string('result_code', 255)->comment("业务结果")->nullable();
            $table->string('err_code', 255)->comment("错误代码	")->nullable();
            $table->string('err_code_des', 255)->comment("错误代码描述")->nullable();
            $table->string('appid', 255)->comment("公众账号ID")->nullable();
            $table->string('mch_id', 255)->comment("商户号")->nullable();
            $table->string('device_info', 255)->comment("设备号")->nullable();
            $table->string('nonce_str', 255)->comment("随机字符串")->nullable();
            $table->string('sign', 255)->comment("签名")->nullable();
            $table->string('sign_type', 255)->comment("签名类型")->nullable();
            $table->string('openid', 255)->comment("用户标识")->nullable();
            $table->integer('is_subscribe')->length(11)->comment("是否关注公众账号")->nullable();
            $table->string('trade_type', 255)->comment("交易类型")->nullable();
            $table->string('bank_type', 255)->comment("付款银行")->nullable();
            $table->integer('total_fee')->length(11)->comment("订单总金额，单位为分")->default("0")->nullable();
            $table->integer('settlement_total_fee')->length(11)->comment("应结订单金额=订单金额-非充值代金券金额，应结订单金额<=订单金额。")->nullable();
            $table->string('fee_type', 255)->comment("货币种类")->nullable();
            $table->integer('cash_fee')->length(11)->comment("现金支付金额")->nullable();
            $table->string('cash_fee_type', 255)->comment("现金支付货币类型")->nullable();
            $table->string('transaction_id', 255)->comment("微信支付订单号")->nullable();
            $table->string('out_trade_no', 255)->comment("商户订单号")->nullable();
            $table->string('attach', 255)->comment("商家数据包")->nullable();
            $table->string('time_end', 255)->comment("支付完成时间")->nullable();
            $table->string('raw_data', 5000)->comment("原始数据")->nullable();
            $table->tinyInteger('deal_status')->length(1)->comment("处理状态:0,失败;1,成功;")->default("0")->nullable();
            $table->string('error_msg', 255)->comment("处理错误信息")->nullable();
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->comment = '日志-微信支付回调';
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
        //Schema::dropIfExists('log_wechatpay_notify');
    }
}
