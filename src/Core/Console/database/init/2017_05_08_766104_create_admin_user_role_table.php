<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminUserRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('admin_user_role', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('admin_user_id')->length(15)->unsigned()->default("0");
            $table->integer('admin_role_id')->length(15)->unsigned()->default("0");
            $table->comment = '后台管理员角色-权限关联表';
            $table->engine = 'InnoDB';
        });

        DB::table('admin_user_role')->insert([
            'id' => '1',
            'admin_user_id' => '1',
            'admin_role_id' => '1',
        ]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('admin_user_role');
    }
}
