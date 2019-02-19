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
            $table->integer('user_id')->length(11)->comment("用户id")->default("0");
            $table->integer('admin_id')->length(11)->comment("管理员id")->default("0");
            $table->integer('part_num')->length(11)->comment("分块总数")->default("1");
            $table->integer('total_size')->length(11)->comment("总大小(K)")->default("0");
            $table->integer('part_size')->length(11)->comment("分块大小(K)")->default("1");
            $table->string('origin_filename', 255)->comment("源文件名")->default("");
            $table->string('filename', 255)->comment("生成文件名")->default("");
            $table->string('path', 255)->comment("文件完整路径")->default("");
            $table->string('file_type', 255)->comment("文件类型")->default("");
            $table->string('type', 255)->comment("上传方式:cloud,云;local,本地;")->default("local");
            $table->string('cloud_type', 255)->comment("云类型:local,qcloud,aliyun")->default("local");
            $table->string('dir', 255)->comment("保存目录")->default("");
            $table->integer('part_now')->length(11)->comment("当前分块进度")->default("0");
            $table->integer('status')->length(11)->comment("状态:0,未完成;1,已完成;")->default("0");
            $table->tinyInteger('is_multi')->length(1)->comment("是否分块上传")->default("0");
            $table->tinyInteger('is_cloud')->length(1)->comment("本地上传后是否上传到云盘")->default("0");
            $table->string('oss_upload_id', 255)->comment("阿里云upload_id")->default("");
            $table->text('oss_part_upload_ids')->comment("oss分块id")->default("");
            $table->string('part_temp_dir', 255)->comment("分块临时目录")->default("");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->comment("用户状态。0为已删除，1为正常")->default("1");
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
