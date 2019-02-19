<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogAdminActionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('log_admin_action', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('admin_id')->length(11)->unsigned()->default("0");
            $table->string('content', 5000)->comment("操作内容")->default("");
            $table->integer('ip')->length(11)->default("0");
            $table->text('data')->default("");
            $table->string('code', 255)->comment("操作控制器代码")->default("");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->comment = '管理员操作日志表';
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
        //Schema::dropIfExists('log_admin_action');
    }
}
