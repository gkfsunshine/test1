<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('config', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('code', 255)->comment("配置编码")->default("");
            $table->string('desc', 255)->comment("说明")->default("");
            $table->text('value')->comment("配置值");
            $table->string('unit', 255)->comment("单位")->default("");
            $table->tinyInteger('is_show')->length(1)->unsigned()->comment("是否可见")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->default("1");
            $table->comment = '配置表';
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
        //Schema::dropIfExists('config');
    }
}
