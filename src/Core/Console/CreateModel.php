<?php

namespace JiaLeo\Laravel\Core\Console;

use Illuminate\Console\Command;

class CreateModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all model files';

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
        //获取当前所有表
        $tables = array_map('reset', \DB::select('SHOW TABLES'));

        //获取模板文件
        if (config('app.model_version') == 'v2') {
            $template = file_get_contents(dirname(__FILE__) . '/stubs/modelV2.stub');
        } else {
            $template = file_get_contents(dirname(__FILE__) . '/stubs/model.stub');
        }

        $template_method = file_get_contents(dirname(__FILE__) . '/stubs/model_method.stub');

        //model文件目录
        $model_path = app_path() . '/Model';

        //加载helper
        load_helper('File');
        $now_table_model_list = []; // 保存当前应该有的model名称
        foreach ($tables as $key => $v) {
            $class_name = studly_case($v) . 'Model';
            $file_name = $class_name . '.php';
            $file_path = $model_path . '/' . $file_name;
            $now_table_model_list[] = $file_name;
            
            //判断文件是否存在,存在则跳过
            if (file_exists($file_path)) {
                continue;
            }

            //查询所有字段
            $columns_ide = '';
            $columns = \DB::select('SHOW COLUMNS FROM `' . $v . '`');
            foreach ($columns as $vv) {

                if (strpos($vv->Type, "int") !== false)
                    $type = 'int';
                else if (strpos($vv->Type, "varchar") !== false || strpos($vv->Type, "char") !== false || strpos($vv->Type, 'blob') || strpos($vv->Type, "text") !== false) {
                    $type = "string";
                } else if (strpos($vv->Type, "decimal") !== false || strpos($vv->Type, "float") !== false || strpos($vv->Type, "double") !== false) {
                    $type = "float";
                } else {
                    $type = 'string';
                }

                $columns_ide .= ' * @property ' . $type . ' $' . $vv->Field . PHP_EOL;
            }

            $columns_ide .= ' *';
            $template_temp = $template;
            $source = str_replace('{{class_name}}', $class_name, $template_temp);
            $source = str_replace('{{table_name}}', $v, $source);
            $source = str_replace('{{ide_property}}', $columns_ide, $source);
            $source_method = str_replace('{{class_name}}', '\App\Model\\' . $class_name, $template_method);
            $source = str_replace('{{ide_method}}', $source_method, $source);

            //写入文件
            if (!dir_exists($model_path)) {
                $this->error('目录' . $model_path . ' 无法写入文件,创建' . $class_name . ' 失败');
                continue;
            }

            if (file_put_contents($file_path, $source)) {
                $this->info($class_name . '添加类成功');
            } else {
                $this->error($class_name . '类写入失败');
            }

        }

        $result = scandir(app_path('Model')); // 读取整个Model文件夹

        $delete_model_list = [];
        foreach ($result as $value) {
            if (strpos($value, '.php') > 0 && !in_array(trim($value), $now_table_model_list)) { // 循环获取没有对应数据表的Model类
                $delete_model_list[] = $value;
            }
        }

        if (!empty($delete_model_list)) {

            $this->warn('检测到当前有以下Model类没有对应的数据表:' . PHP_EOL . implode(PHP_EOL, $delete_model_list));

            foreach ($delete_model_list as $value) { // 开始实行循环删除
                if ($this->confirm('是否需要删除' . $value . '? [y|n]')) { // 询问是否确认删除
                    $res = unlink(app_path('Model/' . $value));
                    if (!$res) {
                        $this->error(app_path('Model/' . $value) . '删除失败');
                    }
                    $this->info('已成功删除' . $value);
                }
            }
        }

    }

}
