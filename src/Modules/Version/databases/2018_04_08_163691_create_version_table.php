<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVersionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('version', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->tinyInteger('type')->length(1)->unsigned()->comment("类型:1,ios;2,安卓;")->default("1");
            $table->string('version', 255)->comment("版本号title");
            $table->integer('version_num')->length(255)->unsigned()->comment("版本号,判断版本大小");
            $table->string('download_url', 255)->comment("下载url")->default("");
            $table->text('content')->comment("更新说明");
            $table->tinyInteger('is_coerce')->length(1)->unsigned()->comment("是否强制更新")->default("0");
            $table->tinyInteger('is_remind')->length(1)->unsigned()->comment("是否提醒更新")->default("0");
            $table->tinyInteger('is_external')->length(1)->unsigned()->comment("是否使用外部链接")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->default("1");
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
        //Schema::dropIfExists('version');
    }
}
