<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdvertisingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('advertising', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255)->comment("标题")->default("");
            $table->string('desc', 255)->comment("描述")->default("");
            $table->string('cover', 255)->comment("广告封面")->default("");
            $table->string('sort', 255)->comment("权重，越大越靠前")->default("1")->nullable();
            $table->tinyInteger('device_platform')->length(1)->unsigned()->comment("设备平台: 1,手机端; 2,APP端;3,PC端;")->default("1");
            $table->tinyInteger('position')->length(1)->unsigned()->comment("广告位置，对应config表")->default("0");
            $table->string('link', 255)->comment("跳转地址，空为不跳转")->default("")->nullable();
            $table->tinyInteger('is_show')->length(1)->comment("是否显示")->default("1")->nullable();
            $table->bigInteger('created_at')->length(15)->default("0")->nullable();
            $table->bigInteger('updated_at')->length(15)->default("0")->nullable();
            $table->tinyInteger('is_on')->length(1)->default("1")->nullable();
            $table->comment = '广告';
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
        //Schema::dropIfExists('advertising');
    }
}
