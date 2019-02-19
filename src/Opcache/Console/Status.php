<?php

namespace JiaLeo\Laravel\Opcache\Console;

use Illuminate\Console\Command;
use JiaLeo\Laravel\Opcache\OpcacheClass;
use Illuminate\Support\Facades\Crypt as Crypt;

class Status extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opcache:status {action=default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show state information, memory usage, etc..';

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

        //cli status
        if ($action == 'default' || $action == 'cli') {
            $res = OpcacheClass::getStatus();
            if (!$res) {
                $this->error('No OPcache status information available');
            } else {
                $this->line('Cli OPcache status information :');
                $this->displayTables($res, 'Cli');
            }
        }

        //web status
        if ($action == 'default' || $action == 'web') {
            load_helper('Network');
            try {
                $host = env('OPCACHE_URL', config('app.url'));
                $key = Crypt::encrypt('opcache');
                $url = $host . '/opcache/status?key=' . $key;
                $res = http_get($url);
                $res = json_decode($res, true);
                if (!$res['result']) {
                    $this->error('No OPcache status information available');
                } else {
                    $this->line('Web OPcache status information :');
                    $this->displayTables($res['result'], 'Web');
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

    /**
     * Display info tables.
     *
     * @param $data
     */
    protected function displayTables($data, $action)
    {
        $this->line($action . ' General:');
        $general = (array)$data;

        unset($general['memory_usage'], $general['interned_strings_usage'], $general['opcache_statistics']);

        $this->table(['key', 'value'], $this->parseTable($general));

        $this->line(PHP_EOL . $action . ' Memory usage:');
        $this->table(['key', 'value'], $this->parseTable($data['memory_usage']));

        $this->line(PHP_EOL . $action . ' Interned strings usage:');
        $this->table(['key', 'value'], $this->parseTable($data['interned_strings_usage']));

        $this->line(PHP_EOL . $action . ' Statistics:');
        $this->table(['option', 'value'], $this->parseTable($data['opcache_statistics']));
    }

}
