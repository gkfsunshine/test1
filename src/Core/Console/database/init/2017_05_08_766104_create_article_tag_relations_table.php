<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticleTagRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('article_tag_relations', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('article_id')->length(15)->unsigned()->default("0");
            $table->integer('tag_id')->length(15)->unsigned()->default("0");
            $table->comment = '文章标签关联表';
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
        //Schema::dropIfExists('article_tag_relations');
    }
}
