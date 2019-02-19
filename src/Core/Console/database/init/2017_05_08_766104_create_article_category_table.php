<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticleCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('article_category', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('name', 255)->comment("标题名称")->default("");
            $table->integer('parent_id')->length(11)->comment("父级id")->unsigned()->default("0")->default("0");
            $table->integer('article_num')->length(11)->comment("文章数量")->unsigned()->default("0")->default("0");
            $table->integer('level')->length(11)->comment("层级")->unsigned()->default("1")->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->comment("0为已删除，1为正常")->default("1");
            $table->comment = '文章分类表';
            $table->engine = 'InnoDB';
        });

        DB::table('article_category')->insert([
            'id' => '43',
            'name' => '新闻资讯',
            'parent_id' => '0',
            'article_num' => '0',
            'level' => '1',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('article_category')->insert([
            'id' => '44',
            'name' => '关于我们',
            'parent_id' => '0',
            'article_num' => '1',
            'level' => '1',
            'created_at' => get_msectime(),
            'updated_at' => get_msectime(),
            'is_on' => '1',
        ]);

        DB::table('article_category')->insert([
            'id' => '45',
            'name' => '联系我们',
            'parent_id' => '44',
            'article_num' => '0',
            'level' => '2',
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
        //Schema::dropIfExists('article_category');
    }
}
