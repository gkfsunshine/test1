<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminRolePermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('admin_role_permission', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('admin_role_id')->length(11)->unsigned()->defalut("0");
            $table->integer('admin_permission_id')->length(11)->unsigned()->defalut("0");
            $table->comment = '后台管理员角色权限表';
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
        //Schema::dropIfExists('admin_role_permission');
    }
}
