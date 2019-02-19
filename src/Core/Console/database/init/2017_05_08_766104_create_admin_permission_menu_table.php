<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminPermissionMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('admin_permission_menu', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('admin_permission_id')->length(11)->unsigned()->default("0");
            $table->integer('admin_menu_id')->length(11)->unsigned()->default("0");
            $table->comment = '后台管理权限菜单-关联表';
            $table->engine = 'InnoDB';
        });

        DB::table('admin_permission_menu')->insert([
            'id' => '2',
            'admin_permission_id' => '9',
            'admin_menu_id' => '3',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '3',
            'admin_permission_id' => '10',
            'admin_menu_id' => '3',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '4',
            'admin_permission_id' => '11',
            'admin_menu_id' => '3',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '5',
            'admin_permission_id' => '12',
            'admin_menu_id' => '4',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '6',
            'admin_permission_id' => '13',
            'admin_menu_id' => '4',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '7',
            'admin_permission_id' => '14',
            'admin_menu_id' => '4',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '8',
            'admin_permission_id' => '15',
            'admin_menu_id' => '4',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '9',
            'admin_permission_id' => '16',
            'admin_menu_id' => '4',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '10',
            'admin_permission_id' => '17',
            'admin_menu_id' => '4',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '11',
            'admin_permission_id' => '18',
            'admin_menu_id' => '5',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '12',
            'admin_permission_id' => '19',
            'admin_menu_id' => '5',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '13',
            'admin_permission_id' => '20',
            'admin_menu_id' => '5',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '14',
            'admin_permission_id' => '21',
            'admin_menu_id' => '5',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '15',
            'admin_permission_id' => '22',
            'admin_menu_id' => '5',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '16',
            'admin_permission_id' => '23',
            'admin_menu_id' => '5',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '17',
            'admin_permission_id' => '24',
            'admin_menu_id' => '6',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '18',
            'admin_permission_id' => '25',
            'admin_menu_id' => '6',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '19',
            'admin_permission_id' => '26',
            'admin_menu_id' => '6',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '20',
            'admin_permission_id' => '27',
            'admin_menu_id' => '6',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '21',
            'admin_permission_id' => '28',
            'admin_menu_id' => '7',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '22',
            'admin_permission_id' => '29',
            'admin_menu_id' => '7',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '23',
            'admin_permission_id' => '30',
            'admin_menu_id' => '7',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '24',
            'admin_permission_id' => '31',
            'admin_menu_id' => '7',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '25',
            'admin_permission_id' => '32',
            'admin_menu_id' => '8',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '26',
            'admin_permission_id' => '33',
            'admin_menu_id' => '8',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '27',
            'admin_permission_id' => '34',
            'admin_menu_id' => '8',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '28',
            'admin_permission_id' => '35',
            'admin_menu_id' => '8',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '29',
            'admin_permission_id' => '36',
            'admin_menu_id' => '9',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '30',
            'admin_permission_id' => '37',
            'admin_menu_id' => '9',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '31',
            'admin_permission_id' => '38',
            'admin_menu_id' => '9',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '32',
            'admin_permission_id' => '39',
            'admin_menu_id' => '9',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '33',
            'admin_permission_id' => '41',
            'admin_menu_id' => '11',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '34',
            'admin_permission_id' => '48',
            'admin_menu_id' => '15',
        ]);

        DB::table('admin_permission_menu')->insert([
            'id' => '35',
            'admin_permission_id' => '61',
            'admin_menu_id' => '17',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('admin_permission_menu');
    }
}
