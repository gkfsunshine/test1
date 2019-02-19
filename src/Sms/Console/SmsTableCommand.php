<?php

namespace JiaLeo\Laravel\Sms\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

class SmsTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:sms:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold create log_sms table';

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
        if (\Schema::hasTable('log_sms')) {
            $this->error('已存在 log_sms 表!');
            return;
        }

        //create table
        \Schema::create('log_sms', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('phone')->length(20)->default(0)->unsigned()->comment('手机号码');
            $table->text('content')->comment('内容')->default('');
            $table->tinyInteger('type')->length(2)->unsigned()->default(0)->comment('短信类型');
            $table->tinyInteger('send_result')->length(1)->unsigned()->default(1)->comment('是否发送成功');
            $table->string('error_msg')->length(5000)->comment('错误信息')->default('');
            $table->integer('created_at')->length(15)->unsigned()->default(0);
            $table->integer('updated_at')->length(15)->unsigned()->default(0);
            $table->tinyInteger('is_on')->length(1)->unsigned()->default(1);

        });

        $this->info('log_sms表生成完毕!');
    }
}
