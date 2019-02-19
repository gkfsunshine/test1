<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUploadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('upload', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->length(11)->comment("用户id")->nullable();
            $table->integer('admin_id')->length(11)->comment("管理员id")->nullable();
            $table->integer('part_num')->length(11)->comment("分块总数")->default("1")->nullable();
            $table->integer('total_size')->length(11)->comment("总大小(K)")->nullable();
            $table->integer('part_size')->length(11)->comment("分块大小(K)")->default("1")->nullable();
            $table->string('origin_filename', 255)->comment("源文件名")->nullable();
            $table->string('filename', 255)->comment("生成文件名")->nullable();
            $table->string('path', 255)->comment("文件完整路径")->nullable();
            $table->string('file_type', 255)->comment("文件类型")->nullable();
            $table->string('type', 255)->comment("上传方式:cloud,云;local,本地;")->default("local")->nullable();
            $table->string('dir', 255)->comment("保存目录")->nullable();
            $table->integer('part_now')->length(11)->comment("当前分块进度")->nullable();
            $table->integer('status')->length(11)->comment("状态:0,未完成;1,已完成;")->nullable();
            $table->tinyInteger('is_multi')->length(1)->comment("是否分块上传")->nullable();
            $table->tinyInteger('is_cloud')->length(1)->comment("本地上传后是否上传到云盘")->nullable();
            $table->string('oss_upload_id', 255)->comment("阿里云upload_id")->nullable();
            $table->text('oss_part_upload_ids')->comment("oss分块id")->nullable();
            $table->string('part_temp_dir', 255)->comment("分块临时目录")->nullable();
            $table->integer('created_at')->length(15)->nullable();
            $table->integer('updated_at')->length(15)->nullable();
            $table->tinyInteger('is_on')->length(1)->default("1")->nullable();
            $table->comment = '上传表';
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
        //Schema::dropIfExists('upload');
    }
}
