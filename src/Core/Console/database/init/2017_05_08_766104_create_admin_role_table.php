<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('admin_role', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('name', 255)->comment("角色名称")->default("");
            $table->string('description', 255)->comment("角色描述")->default("");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->comment("0为已删除，1为正常")->default("1");
            $table->comment = '后台管理员角色表';
            $table->engine = 'InnoDB';
        });

        DB::table('admin_role')->insert([
            'id' => '1',
            'name' => '超级管理员',
            'description' => '超级管理员',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_role')->insert([
            'id' => '6',
            'name' => '运营',
            'description' => '负责运营',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('admin_role');
    }
}
