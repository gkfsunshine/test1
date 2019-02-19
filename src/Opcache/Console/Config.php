<?php

namespace JiaLeo\Laravel\Opcache\Console;

use Illuminate\Console\Command;
use JiaLeo\Laravel\Opcache\OpcacheClass;
use Illuminate\Support\Facades\Crypt as Crypt;

class Config extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opcache:config {action=default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show your opcode cache configuration';

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
        $action = $this->argument('action');

        //cli config
        if ($action == 'default' || $action == 'cli') {
            $res = OpcacheClass::getConfig();
            if (!$res) {
                $this->error('No OPcache configuration found');
            } else {
                $this->line('Cli Version info:');
                $this->table(['key', 'value'], $this->parseTable($res['version']));

                $this->line(PHP_EOL . 'Cli Configuration info:');
                $this->table(['option', 'value'], $this->parseTable($res['directives']));
            }
        }

        //web config
        if ($action == 'default' || $action == 'web') {
            load_helper('Network');
            try {
                $host = empty(env('OPCACHE_URL'))?config('app.url'):env('OPCACHE_URL');

                $key = Crypt::encrypt('opcache');
                $url = $host . '/opcache/config?key=' . $key;
                $res = http_get($url);
                $res = json_decode($res, true);
                if (!$res['result']) {
                    $this->error('No OPcache configuration found');
                } else {
                    $this->line('Web Version info:');
                    $this->table(['key', 'value'], $this->parseTable($res['result']['version']));

                    $this->line(PHP_EOL . 'Web Configuration info:');
                    $this->table(['option', 'value'], $this->parseTable($res['result']['directives']));
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

    }

    /**
     * Make up the table for console display.
     *
     * @param $input
     *
     * @return array
     */
    protected function parseTable($input)
    {
        $input = (array)$input;

        return array_map(function ($key, $value) {
            $bytes = ['opcache.memory_consumption'];

            if (in_array($key, $bytes)) {
                $value = number_format($value / 1048576, 2) . ' MB';
            }

            return [
                'key' => $key,
                'value' => $value,
            ];
        }, array_keys($input), $input);
    }

}
