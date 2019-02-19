<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogUserBalanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('log_user_balance', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->length(11)->unsigned()->default("0");
            $table->integer('balance')->length(11)->unsigned()->comment("原有余额(单位:分)")->default("0");
            $table->integer('amount')->length(11)->unsigned()->comment("变动金额(单位：分)")->default("0");
            $table->tinyInteger('is_plus')->length(1)->unsigned()->comment("是否为正")->default("1");
            $table->tinyInteger('type')->length(1)->unsigned()->comment("变动类型:1,余额充值;")->default("1");
            $table->bigInteger('transaction_id')->length(20)->unsigned()->comment("交易流水号")->default("0");
            $table->integer('admin_id')->length(11)->unsigned()->comment("管理员id")->default("0");
            $table->tinyInteger('pay_method')->length(1)->unsigned()->comment("充值支付方式:1,微信;2,支付宝;3,银联在线;4,易宝支付;")->default("1");
            $table->integer('order_id')->length(11)->unsigned()->comment("关联订单id")->default("0");
            $table->text('remark')->comment("备注");
            $table->string('out_trade_no', 50)->comment("第三方支付订单号")->default("");
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->default("1");
            $table->comment = '用户余额日志表';
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
        //Schema::dropIfExists('log_user_balance');
    }
}
