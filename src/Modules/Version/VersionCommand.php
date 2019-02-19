<?php

namespace JiaLeo\Laravel\Modules\Version;

use App\Exceptions\ApiException;
use App\Model\AdminMenuModel;
use App\Model\AdminPermissionModel;
use Illuminate\Console\Command;

class VersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create version module';

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
        load_helper('File');

        //migrate
        $this->createMigrate();

        //model
        if ($this->createModel()) {
            $this->info('生成model成功!');
        }

        //controller
        if ($this->createController()) {
            $this->info('生成controller成功!');
        }

        //createLogic
        if ($this->createLogic()) {
            $this->info('生成logic成功!');
        }

        // insertData
        $route_id1 = 1000002;
        $route_id2 = 1000003;
        $route_id3 = 1000004;
        if ($this->insertData($route_id1, $route_id2, $route_id3)) {
            $this->info('插入必需数据成功!');
        }

        //route
        $this->warn(str_repeat('*', 15));
        $this->warn("请手动将 " . PHP_EOL . "Route::resource('versions', 'Admin\VersionController',['as' => 'name:APP版本管理权限组:APP版本|menu:APP版本管理:APP版本列表|route_id:'" . $route_id1 . "]); // 版本管理" . PHP_EOL . "Route::get('version/instructions', 'Admin\VersionController@getAppInstructions')->name('name:APP版本管理权限组:查看APP介绍|route_id:'" . $route_id2 . "); // 获取APP介绍" . PHP_EOL . "Route::post('version/instructions', 'Admin\VersionController@postAppInstructions')->name('name:APP版本管理权限组:编辑APP介绍|route_id:'" . $route_id3 . "); // 编辑APP介绍" . PHP_EOL . "添加到route/admin.php");
        $this->warn("请手动将 Route::get('versions','Api\VersionController@index'); // 最新APP版本 添加到route/api.php");
    }

    /**
     * 创建migrate
     */
    private function createMigrate()
    {
        //先存放到临时文件夹
        $dist = 'storage/migrations/' . date('YmdHis');
        $dist_path = base_path($dist);
        dir_exists($dist_path);
        $is_copy = copy_dir(__DIR__ . '/databases', $dist_path);

        if (!$is_copy) {
            $this->error('创建migrate--复制临时文件失败,请确保storage目录有权限!');
            return false;
        }

        $this->call('migrate', [
            '--path' => $dist
        ]);

        //删除文件夹
        $is_del = del_dir($dist_path);
        if (!$is_del) {
            $this->error('创建migrate--删除临时文件失败,请自行删除!' . $dist_path);
            return false;
        }

        return true;
    }

    /**
     * 生成model
     * @return bool
     */
    private function createModel()
    {
        $dist_path = app_path('Model');
        $is_copy = copy_stubs(__DIR__ . '/stubs/model', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建model--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }

    /**
     * 生成controller
     * @return bool
     */
    private function createController()
    {
        $dist_path = app_path('Http/Controllers');
        $is_copy = copy_stubs(__DIR__ . '/stubs/controller', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建controller--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }

    /**
     * 生成Logic
     * @return bool
     */
    private function createLogic()
    {
        $dist_path = app_path('Logic');
        $is_copy = copy_stubs(__DIR__ . '/stubs/logic', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建logic--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }

    /**
     * 插入必需数据
     * @return bool
     */
    private function insertData($route_id1, $route_id2, $route_id3)
    {
        try {
            \DB::beginTransaction();
            // 插入菜单数据
            $menu_parent = \App\Model\AdminMenuModel::where('name', 'APP版本管理')
                ->where('is_on', 1)
                ->where('parent_id', 0)
                ->where('level', 1)
                ->first(['id']);

            if (!$menu_parent) {
                $menu_parent = new \App\Model\AdminMenuModel();
                set_save_data($menu_parent, [
                    'name' => 'APP版本管理',
                    'level' => 1,
                    'parent_id' => 0,
                    'order' => 1,
                    'description' => 'APP版本管理',
                ]);
                $res = $menu_parent->save();
                if (!$res) {
                    throw new ApiException('插入必需数据失败！');
                }
            }

            $menu_parent_id = $menu_parent->id;

            $menu_model = new \App\Model\AdminMenuModel();
            set_save_data($menu_model, [
                'name' => 'APP版本列表',
                'url' => '/system/version/lists',
                'level' => 2,
                'parent_id' => $menu_parent_id,
                'order' => 1,
                'description' => 'APP版本列表',
            ]);

            $res = $menu_model->save();
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }

            // 插入权限数据
            $permission_parent = \App\Model\AdminPermissionModel::where('name', 'APP版本管理权限组')
                ->where('is_on', 1)
                ->where('parent_id', 0)
                ->where('level', 1)
                ->first(['id']);

            if (!$permission_parent) {

                $permission_parent = new \App\Model\AdminPermissionModel();
                set_save_data($permission_parent, [
                    'name' => 'APP版本管理权限组',
                    'level' => 1,
                    'parent_id' => 0,
                    'description' => 'APP版本管理权限组',
                ]);

                $res = $permission_parent->save();
                if (!$res) {
                    throw new ApiException('插入必需数据失败！');
                }
            }

            $permission_parent_id = $permission_parent->id;

            $permission_model = new \App\Model\AdminPermissionModel();
            set_save_data($permission_model, [
                'name' => 'APP版本列表',
                'description' => 'APP版本列表',
                'code' => 'Version@index',
                'level' => 2,
                'route_id' => $route_id1,
                'parent_id' => $permission_parent_id
            ]);
            $res = $permission_model->save();
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }

            $res = \DB::table('admin_permission')->insert([
                'name' => 'APP版本详情',
                'level' => 2,
                'route_id' => $route_id1,
                'parent_id' => $permission_parent_id,
                'description' => 'APP版本详情',
                'code' => 'Version@show'
            ]);
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }

            $res = \DB::table('admin_permission')->insert([
                'name' => '添加APP版本',
                'level' => 2,
                'route_id' => $route_id1,
                'parent_id' => $permission_parent_id,
                'description' => '添加APP版本',
                'code' => 'Version@store'
            ]);
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }

            $res = \DB::table('admin_permission')->insert([
                'name' => '编辑APP版本',
                'level' => 2,
                'route_id' => $route_id1,
                'parent_id' => $permission_parent_id,
                'description' => '编辑APP版本',
                'code' => 'Version@update'
            ]);
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }

            $res = \DB::table('admin_permission')->insert([
                'name' => '删除APP版本',
                'level' => 2,
                'route_id' => $route_id1,
                'parent_id' => $permission_parent_id,
                'description' => '删除APP版本',
                'code' => 'Version@destroy'
            ]);
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }

            $res = \DB::table('admin_permission')->insert([
                'name' => '查看APP介绍',
                'level' => 2,
                'route_id' => $route_id2,
                'parent_id' => $permission_parent_id,
                'description' => '查看APP介绍',
                'code' => 'Version@getAppInstructions'
            ]);
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }

            $res = \DB::table('admin_permission')->insert([
                'name' => '编辑APP介绍',
                'level' => 2,
                'route_id' => $route_id3,
                'parent_id' => $permission_parent_id,
                'description' => '编辑APP介绍',
                'code' => 'Version@postAppInstructions'
            ]);
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }

            // 添加菜单权限关联
            $res = \DB::table('admin_permission_menu')->insert([
                'admin_permission_id' => $menu_model->id,
                'admin_menu_id' => $permission_model->id
            ]);
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }

            // 添加菜单权限关联
            $res = \DB::table('config')->insert([
                'code' => 'app_instructions',
                'desc' => 'app介绍文本',
                'value' => '汉子科技',
                'updated_at' => time(),
                'created_at' => time(),
            ]);
            if (!$res) {
                throw new ApiException('插入必需数据失败！');
            }


            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->error($e);
            return false;
        }
    }
}
