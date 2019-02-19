<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('admin_menu', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('name', 255)->comment("菜单名称")->default("");
            $table->string('description', 255)->comment("菜单描述")->default("");
            $table->string('url', 255)->comment("跳转地址")->default("");
            $table->string('icon', 255)->comment("图标地址")->default("");
            $table->tinyInteger('level')->length(1)->unsigned()->comment("层级")->default("1");
            $table->integer('parent_id')->length(11)->unsigned()->comment("父级id")->default("0");
            $table->integer('order')->length(11)->unsigned()->comment("排序")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->comment("0为已删除，1为正常")->default("1");
            $table->comment = '管理员-菜单表';
            $table->engine = 'InnoDB';
        });

        DB::table('admin_menu')->insert([
            'id' => '1',
            'name' => '管理员管理',
            'description' => '管理员管理',
            'url' => '',
            'icon' => '',
            'level' => '1',
            'parent_id' => '0',
            'order' => '1',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '2',
            'name' => '文章管理',
            'description' => '文章管理',
            'url' => '',
            'icon' => '',
            'level' => '1',
            'parent_id' => '0',
            'order' => '2',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '3',
            'name' => '管理员列表',
            'description' => '管理员列表',
            'url' => '/admin/lists/lists',
            'icon' => 'fa-list-ul',
            'level' => '2',
            'parent_id' => '1',
            'order' => '1',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '4',
            'name' => '角色列表',
            'description' => '角色列表',
            'url' => '/admin/role/lists',
            'icon' => 'fa-list-ul',
            'level' => '2',
            'parent_id' => '1',
            'order' => '2',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '5',
            'name' => '权限组列表',
            'description' => '权限组列表',
            'url' => '/admin/power/lists',
            'icon' => 'fa-list-ul',
            'level' => '2',
            'parent_id' => '1',
            'order' => '3',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '6',
            'name' => '菜单组列表',
            'description' => '菜单组列表',
            'url' => '/admin/menu/lists',
            'icon' => 'fa-list-ul',
            'level' => '2',
            'parent_id' => '1',
            'order' => '4',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '7',
            'name' => '文章列表',
            'description' => '文章列表',
            'url' => '/article/lists/lists',
            'icon' => 'fa-list-ul',
            'level' => '2',
            'parent_id' => '2',
            'order' => '1',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '8',
            'name' => '文章分类',
            'description' => '文章分类',
            'url' => '/article/classify/lists',
            'icon' => 'fa-list-ul',
            'level' => '2',
            'parent_id' => '2',
            'order' => '2',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '9',
            'name' => '文章标签',
            'description' => '文章标签',
            'url' => '/article/label/lists',
            'icon' => 'fa-list-ul',
            'level' => '2',
            'parent_id' => '2',
            'order' => '3',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '10',
            'name' => '会员管理',
            'description' => '会员管理',
            'url' => '',
            'icon' => '',
            'level' => '1',
            'parent_id' => '0',
            'order' => '3',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '11',
            'name' => '会员列表',
            'description' => '会员列表',
            'url' => '/user/lists',
            'icon' => '',
            'level' => '2',
            'parent_id' => '10',
            'order' => '1',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '15',
            'name' => '管理员操作日志',
            'description' => '管理员操作日志',
            'url' => '/admin/log/lists',
            'icon' => '',
            'level' => '2',
            'parent_id' => '1',
            'order' => '5',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '16',
            'name' => '系统设置',
            'description' => '系统设置',
            'level' => '1',
            'parent_id' => '0',
            'order' => '6',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_menu')->insert([
            'id' => '17',
            'name' => '基础设置',
            'description' => '基础设置',
            'url' => '/system/config',
            'level' => '2',
            'parent_id' => '16',
            'order' => '1',
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
        //Schema::dropIfExists('admin_menu');
    }
}
