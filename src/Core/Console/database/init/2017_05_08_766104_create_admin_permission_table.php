<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminPermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('admin_permission', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('name', 255)->comment("权限名称")->default("");
            $table->string('code', 255)->comment("规则代码")->default("");
            $table->string('description', 255)->comment("描述")->default("");
            $table->integer('parent_id')->length(11)->unsigned()->comment("父级id")->default("0");
            $table->tinyInteger('level')->length(1)->unsigned()->comment("层级，1级为组，2级为权限")->default("2");
            $table->integer('sort')->length(1)->unsigned()->comment("排序(从小到大)")->default("1");
            $table->integer('route_id')->length(11)->unsigned()->comment("路由id")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->comment("0为已删除，1为正常")->default("1");
            $table->comment = '后台管理权限表';
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
        //Schema::dropIfExists('admin_permission');
    }
}
