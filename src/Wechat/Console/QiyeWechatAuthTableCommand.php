<?php

namespace JiaLeo\Laravel\Wechat\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

class QiyeWechatAuthTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:qiyewechat:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold create qiye_auth_oauth table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (\Schema::hasTable('qiye_auth_oauth')) {
            $this->error('已存在 qiye_auth_oauth 表!');
            return;
        }

        //create table
        \Schema::create('qiye_auth_oauth', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('qiye_user_id')->length(11)->default("0")->comment('需要使用企业微信的用户userid');
            $table->tinyInteger('oauth_type')->length(1)->unsigned()->comment("OAuth类型:1,企业微信;2,qq;3,微博;")->default("1");
            $table->string('nickname', 255)->comment("昵称")->default("");
            $table->string('headimg', 255)->comment("头像")->default("");
            $table->bigInteger('phone')->length(20)->default(0)->unsigned()->comment('手机号码');
            $table->string('email', 255)->comment("企业员工email")->default("");
            $table->string('id1', 255)->comment("授权id:对应企业微信的UserId")->default("");
            $table->string('id2', 255)->comment("授权id2:对应企业微信的unionid_id")->default("");
            $table->string('access_token', 255)->default("");
            $table->string('refresh_token', 255)->default("");
            $table->integer('expires_time')->length(11)->unsigned()->default("0");
            $table->mediumText('info')->comment("授权信息")->default("");
            $table->integer('created_at')->length(11)->unsigned()->comment("创建时间")->default("0");
            $table->integer('updated_at')->length(11)->unsigned()->comment("更新时间")->default("0");
            $table->comment = '用户企业微信OAuth表';
            $table->engine = 'InnoDB';
        });

        $this->info('qiye_auth_oauth表生成完毕!');
    }
}
