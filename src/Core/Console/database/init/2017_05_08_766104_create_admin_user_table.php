<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('admin_user', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('account', 255)->comment("管理员登录账号")->default("");
            $table->string('password', 255)->comment("管理员登录密码")->default("");
            $table->string('salt', 255)->comment("组合密码加密用的扰乱串")->default("");
            $table->string('name', 255)->comment("管理员昵称")->default("");
            $table->bigInteger('phone')->length(20)->unsigned()->comment("手机号码")->default("0");
            $table->string('headimg', 255)->comment("头像")->default("");
            $table->bigInteger('last_login_ip')->length(20)->unsigned()->comment("用户最后一次登录的ip")->default("0");
            $table->integer('last_login_time')->length(11)->unsigned()->comment("最后登录时间")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->comment("用户状态。0为已删除，1为正常")->default("1");
            $table->comment = '后台管理员表';
            $table->engine = 'InnoDB';
        });

        DB::table('admin_user')->insert([
            'id' => '1',
            'account' => 'admin',
            'password' => '57e9bc9694bbc2a9a9e8e1ddfd0cdce4',
            'salt' => 'D0wIl',
            'name' => '管理员',
            'phone' => '13113768987',
            'headimg' => '',
            'last_login_ip' => '0',
            'last_login_time' => '0',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('admin_user')->insert([
            'id' => '22',
            'account' => 'yunying',
            'password' => '7f318c0d85f91cb9c199af0ad2258b02',
            'salt' => 'X3OoL',
            'name' => '运营',
            'phone' => '0',
            'headimg' => '',
            'last_login_ip' => '0',
            'last_login_time' => '0',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '0',
        ]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('admin_user');
    }
}
