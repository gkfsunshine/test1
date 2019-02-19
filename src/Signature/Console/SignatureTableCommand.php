<?php

namespace JiaLeo\Laravel\Signature\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

class SignatureTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'signature:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Signature Table';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $table = 'access_key';
        if (!\Schema::hasTable($table)) {
            \Schema::create($table, function (Blueprint $table) {
                $table->increments('id');
                $table->string('access_key_id')->length(100)->default('')->comment('key_id');
                $table->string('access_key_secret')->length(200)->default('')->comment('密钥');
                $table->integer('created_at')->length(11)->unsigned()->default(0)->comment("创建时间");
                $table->integer('updated_at')->length(11)->unsigned()->default(0)->comment("更新时间");
                $table->tinyInteger('is_on')->length(1)->unsigned()->default(1)->comment("是否启用");
                $table->comment = '后台管理员角色表';
                $table->engine = 'InnoDB';
            });
            $this->info('access_key表创建成功');
        } else {
            $this->info('access_key表已存在');
        }
    }
}
