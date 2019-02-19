<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('article', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('title', 255)->comment("标题")->default("");
            $table->string('author', 255)->comment("作者")->default("");
            $table->string('cover', 255)->comment("封面url")->default("");
            $table->mediumText('content')->comment("正文")->default("");
            $table->integer('cat_id')->length(11)->unsigned()->comment("分类id")->default("0");
            $table->tinyInteger('is_show')->length(1)->comment("是否显示")->unsigned()->default("0");
            $table->integer('click_num')->length(11)->comment("点击数")->unsigned()->default("0");
            $table->bigInteger('created_at')->length(15)->unsigned()->comment("创建时间")->default("0");
            $table->bigInteger('updated_at')->length(15)->unsigned()->comment("更新时间")->default("0");
            $table->tinyInteger('is_on')->length(1)->unsigned()->comment("0为已删除，1为正常")->default("1");
            $table->comment = '文章表';
            $table->engine = 'InnoDB';
        });

        DB::table('article')->insert([
            'id' => '41',
            'title' => '关于我们 ',
            'author' => '',
            'cover' => '',
            'content' => '<p style="font-family: 微软雅黑; box-sizing: border-box; margin-top: 0px; margin-bottom: 0px; padding: 0px; text-indent: 2em; color: rgb(255, 255, 255); white-space: normal;"><span style="color: rgb(0, 0, 0);">汉子科技专注于移动互联网软件开发与视频技术领域， 公司成立于2014年。</span></p><p style="font-family: 微软雅黑; box-sizing: border-box; margin-top: 0px; margin-bottom: 0px; padding: 0px; text-indent: 2em; color: rgb(255, 255, 255); white-space: normal;"><span style="color: rgb(0, 0, 0);">一直以来，汉子科技为客户提供专业化视频技术部署架构，以及ios、androidH5营销游戏、移动电商系统、O2O应用等移动平台软件开发，php、net等服务器平台开发， 另外还从事APP、微信、web网站、 微信、GUI等产品系统的设计研发。团队成员在互联网领域拥有多年的实践经验， 公司本着开放的管理模式和紧密的团队配合， 已经为多家企事业单位提供了专业优质的服务，并受到了客户的一致好评。</span></p><p><br/></p>',
            'cat_id' => '44',
            'is_show' => '1',
            'click_num' => '0',
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
        //Schema::dropIfExists('article');
    }
}
