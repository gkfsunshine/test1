<?php

namespace JiaLeo\Laravel\Sms\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

class SmsTemplateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:sms:template';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold create sms_template table';

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
        if (\Schema::hasTable('sms_template')) {
            $this->error('已存在 sms_template 表!');
            return;
        }

        //create table
        \Schema::create('sms_template', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->tinyInteger('log_type')->length(4)->comment("短信日志类型")->default("0");
            $table->string('type', 50)->comment("短信类型verification:验证码;notification:通知短信")->default("");
            $table->string('template_code', 50)->comment("短信模板ID")->default("");
            $table->string('title', 255)->comment("短信标题")->default("");
            $table->text('content')->comment("内容");
            $table->tinyInteger('is_edit')->length(1)->comment("是否可编辑")->default("1");
            $table->bigInteger('created_at')->length(15)->default("0");
            $table->bigInteger('updated_at')->length(15)->default("0")->nullable();
            $table->tinyInteger('is_on')->length(1)->default("1")->nullable();
            $table->comment = '短信模板';
            $table->engine = 'InnoDB';
        });

        $this->info('sms_template表生成完毕!');
    }
}
