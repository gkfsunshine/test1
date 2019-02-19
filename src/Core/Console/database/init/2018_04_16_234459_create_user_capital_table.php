<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserCapitalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('user_capital', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->length(11)->unsigned()->comment("用户id")->default("0");
            $table->integer('balance')->length(11)->unsigned()->comment("当前余额(单位:分)")->default("0");
            $table->integer('integral')->length(11)->unsigned()->comment("当前积分")->default("0");
            $table->integer('total_integral')->length(11)->unsigned()->comment("累计积分")->default("0");
            $table->integer('total_recharge')->length(11)->unsigned()->comment("累计充值额(单位:分)")->default("0");
            $table->integer('total_spending')->length(11)->unsigned()->comment("累计消费额(单位:分)")->default("0");
            $table->integer('total_order_num')->length(11)->unsigned()->comment("总订单数((包括全部))")->default("0");
            $table->integer('total_success_order_amount')->length(11)->unsigned()->comment("累计订单额(成功交易的订单)")->default("0");
            $table->integer('total_success_order_num')->length(11)->unsigned()->comment("累计成功交易订单数")->default("0");
            $table->integer('total_refund_order_amount')->length(11)->unsigned()->comment("累计退款金额")->default("0");
            $table->integer('last_order_time')->length(11)->unsigned()->comment("最近下单时间")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->comment = '用户资金表';
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
        //Schema::dropIfExists('user_capital');
    }
}
