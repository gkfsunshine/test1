<?php

namespace JiaLeo\Laravel\Signature\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

class SignatureAddCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'signature:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Access Key';

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
        $res = \Signature::createAccessKey();
        if ($res) {
            $this->info('创建access_key成功,access_key_id=' . $res['access_key_id'] . ',access_key_secret=' . $res['access_key_secret']);
        } else {
            $this->error('创建access_key失败,请检测存储驱动是否正常');
        }
    }
}
