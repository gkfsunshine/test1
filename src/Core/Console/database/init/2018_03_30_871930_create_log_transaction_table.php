<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('log_transaction', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->length(11)->unsigned()->comment("用户id")->default("0");
            $table->tinyInteger('type')->length(1)->unsigned()->comment("交易类型:1,充值余额;")->default("1");
            $table->string('device', 255)->comment("设备类型:web,wap,app")->default("web");
            $table->tinyInteger('is_plus')->length(1)->unsigned()->comment("是否为正")->default("1");
            $table->tinyInteger('pay_method')->length(1)->unsigned()->comment("支付方式:0,余额;1,微信;2,支付宝;3,银联;4,易宝;")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->comment = '日志-交易流水表';
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
        //Schema::dropIfExists('log_transaction');
    }
}
