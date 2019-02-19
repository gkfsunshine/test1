<?php

namespace JiaLeo\Laravel\Core\Console;

use App\Exceptions\ApiException;
use Illuminate\Console\Command;

class CreatePermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:permission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create permission data';

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
        // 读取整个文件(array)
        $route_file_arr = file(base_path('routes/admin.php'));
        // 获取已命名并且没有注释的部分
        $route_list = [];
        $file_route_name_list = [];
        foreach ($route_file_arr as $key => $value) {
            $preg_match1 = preg_match_all("/Route::resource(.*)\[\'as\' {0,}=> {0,}(.*)\]/", $value, $mc1);
            $preg_match2 = preg_match_all("/->name\((.*)\)/", $value, $mc2);
            if (($preg_match1 || $preg_match2) && strpos(ltrim($value), '//') !== 0) {
                if ($preg_match1) {
                    $file_route_name_list[$key] = trim(trim($mc1[2][0], '\''), '"');
                } else {
                    $file_route_name_list[$key] = trim(trim($mc2[1][0], '\''), '"');
                }

                $route_list[$key] = $value;
            }
        }

        // 获取所有命名路由
        $route = \Route::getRoutes()->getRoutesByName();

        $permission_list = \App\Model\AdminPermissionModel::where('is_on', 1)
            ->select(['code', 'id', 'route_id', 'name', 'level', 'parent_id'])
            ->get();

        // 已存在的route_id，用作重复判断
        $exist_route_ids = $permission_list->where('route_id', '<>', 0)->pluck('route_id')->toArray();

        $parents = []; // 用户保存已存在的父级权限
        $permission_list->where('level', 1)->each(function ($item) use (&$parents) {
            $parents[$item->name] = $item->id;
        });

        // 根据route_id存放已存在的权限
        $permission_list = $permission_list->groupBy('route_id');

        // 获取菜单
        $menu_list = \App\Model\AdminPermissionMenuModel::select(['admin_permission_id', 'admin_menu_id'])
            ->get();
        $menu_list = $menu_list->groupBy('admin_permission_id');

        $num = 0;
        $add_permission = []; // 需要添加的权限
        $update_permission = []; // 需要修改的权限
        $delete_permission = []; // 需要删除的权限
        $delete_permission_route_ids = []; // 需要删除的权限的route_id
        $now_route_ids = []; // 当前admin.php文件剩余的route_id
        $update_permission_parent_ids = []; // 此次过程进行更变的权限组ID，用于后面决定是否需要清除
        $lack_menu_list = [];
        foreach ($route as $key => $value) {
            $action = $value->action;
            // 判断是否存在AdminAuth的中间件里，是才进入
            if (isset($action['middleware']) && is_array($action['middleware']) && in_array("AdminAuth", $action['middleware'])) {

                $route_name_array = explode('.', $key); // $key , as的格式 //name:权限组:权限|menu:菜单组:菜单|create:false|edit:false|index:true|store:true|show:true|update:true|destroy:true.资源路由命名(users).资源理由方法(index)

                $resource = ''; // 资源路由当前的function名称

                // 验证命名规范
                if (count($route_name_array) == 1) {
                    $route_name = $action['as'];
                } else if (count($route_name_array) == 2) { // 通过验证则只是没有命名的资源路由
                    $verify = $this->verifyAs($route_name_array[1], $route_name_array[0], $value->uri, $value->methods);
                    if (!$verify) {
                        throw new ApiException('路由命名格式错误' . $key);
                    }
                    continue;
                } else if (count($route_name_array) == 3) {
                    $verify = $this->verifyAs($route_name_array[2], $route_name_array[1], $value->uri, $value->methods);
                    if (!$verify) {
                        throw new ApiException('路由命名格式错误' . $key);
                    }
                    $route_name = $route_name_array[0];
                    $resource = $route_name_array[2];
                } else {
                    throw new ApiException('路由命名格式错误' . $key);
                }

                // 获取权限信息和菜单信息
                $as_arr = explode('|', $route_name); // 权限名称-菜单需要的数据
                $permission = []; // 权限数据
                $menu = []; // 菜单数据
                $is_create = false; // 资源路由默认不创建create的权限
                $is_edit = false; // 资源路由默认不创建edit的权限
                $is_index = true; // 资源路由默认创建index的权限
                $is_store = true; // 资源路由默认创建store的权限
                $is_show = true; // 资源路由默认创建show的权限
                $is_update = true; // 资源路由默认创建update的权限
                $is_destroy = true; // 资源路由默认创建destroy的权限
                $route_id = 0;
                $route_list_key = '';
                foreach ($as_arr as $item) {
                    $v = explode(':', $item);

                    if (!isset($v[1])) {
                        throw new ApiException('格式错误：' . $action['as']);
                    }

                    switch ($v[0]) {
                        case 'name':
                            if (!isset($v[1]) || !isset($v[2])) {
                                throw new ApiException('name格式错误：' . $action['as']);
                            }

                            $route_name_num = 0;
                            foreach ($file_route_name_list as $route_key => $route_value) {
                                $route_value_arr = explode('|', $route_value);
                                foreach ($route_value_arr as $route_name_value) {
                                    if ('name:' . $v[1] . ':' . $v[2] === $route_name_value) {
                                        $route_name_num++;
                                        $route_list_key = $route_key;
                                    }
                                }
                            }

                            if ($route_name_num > 1) {
                                throw new ApiException('存在重复权限：' . $action['as']);
                            } else if ($route_name_num == 0) {
                                throw new ApiException('命名路由格式错误！'. $action['as']);
                            }

                            $permission = [$v[1], $v[2]];
                            break;
                        case 'menu':
                            if (!isset($v[1]) || !isset($v[2])) {
                                throw new ApiException('menu格式错误：' . $action['as']);
                            }
                            $menu = [$v[1], $v[2]];
                            break;
                        case 'create':
                            $is_create = $this->verifyBool($action['as'], $v[1]);
                            break;
                        case 'edit':
                            $is_edit = $this->verifyBool($action['as'], $v[1]);
                            break;
                        case 'index':
                            $is_index = $this->verifyBool($action['as'], $v[1]);
                            break;
                        case 'store':
                            $is_store = $this->verifyBool($action['as'], $v[1]);
                            break;
                        case 'show':
                            $is_show = $this->verifyBool($action['as'], $v[1]);
                            break;
                        case 'update':
                            $is_update = $this->verifyBool($action['as'], $v[1]);
                            break;
                        case 'destroy':
                            $is_destroy = $this->verifyBool($action['as'], $v[1]);
                            break;
                        case 'route_id':
                            $route_id = $v[1];
                            break;
                        default:
                            throw new ApiException('格式错误：' . $action['as']);
                            break;
                    }
                }

                // 没有权限直接报错
                if (empty($permission)) {
                    throw new ApiException('权限格式错误：' . $action['as']);
                }

                // 获取code
                $controller = str_replace($action['namespace'] . '\Admin\\', '', $action['controller']);
                $code = str_replace('Controller@', '@', $controller);

                // 判断是否为资源路由，是的话拼接信息
                $parent_permission_name = $permission[0];
                if (!empty($resource)) {
                    $continue = 0;

                    switch ($resource) {
                        case 'index':
                            if ($is_index) {
                                $permission_name = $permission[1] . '列表';
                            } else {
                                $continue = 1;
                            }
                            break;
                        case 'store':
                            if ($is_store) {
                                $permission_name = '添加' . $permission[1];
                            } else {
                                $continue = 1;
                            }
                            break;
                        case 'show':
                            if ($is_show) {
                                $permission_name = $permission[1] . '详情';
                            } else {
                                $continue = 1;
                            }
                            break;
                        case 'update':
                            if ($is_update) {
                                $permission_name = '编辑' . $permission[1];
                            } else {
                                $continue = 1;
                            }
                            break;
                        case 'destroy':
                            if ($is_destroy) {
                                $permission_name = '删除' . $permission[1];
                            } else {
                                $continue = 1;
                            }
                            break;
                        case 'create':
                            if ($is_create) {
                                $permission_name = $permission[1] . '数据';
                            } else {
                                $continue = 1;
                            }
                            break;
                        case 'edit':
                            if ($is_edit) {
                                $permission_name = $permission[1] . '数据';
                            } else {
                                $continue = 1;
                            }
                            break;
                        default:
                            $continue = 1;
                            break;
                    }
                    if ($continue == 1) {

                        if (isset($permission_list[$route_id])) {
                            $old_data = [];
                            $permission_list[$route_id]->each(function ($item) use (&$old_data, $resource) {
                                if (!empty($item->code) && explode('@', $item->code)[1] == $resource) {
                                    $old_data = $item;
                                }
                            });
                            if (isset($old_data['id'])) {
                                $delete_permission[] = $old_data['id'];
                            }

                        }
                        continue;
                    }
                } else {
                    $permission_name = $permission[1];
                }

                // 判断是否需要绑定目录
                $menu_id = 0;
                if (!empty($menu) && $value->methods[0] == 'GET' && ($resource == 'index' || empty($resource))) {
                    $menu_data = \App\Model\AdminMenuModel::where('name', $menu[1])
                        ->where('is_on', 1)
                        ->where('level', 2)
                        ->first(['id']);

                    if ($menu_data) { // 存在菜单则绑定，不存在则忽略
                        $menu_id = $menu_data['id'];
                    } else {
                        $lack_menu_list[] = $menu[0] . '->' . $menu[1];
                    }
                }

                // 当前不存在route_id且原有权限不存在目前的route_id，则需要生成route_id
                if (empty($route_id) || !isset($permission_list[$route_id])) {
                    if (substr_count($route_list[$route_list_key], 'route_id:') == 0) {
                        $route_id = $this->getRouteId($exist_route_ids);
                        if (!in_array($route_id, $exist_route_ids)) {
                            // 修改路由文件，插入route_id
                            $place = strrpos($route_list[$route_list_key], $route_name);
                            $insertion_place = $place + strlen($route_name);
                            $route_list[$route_list_key] = substr_replace($route_list[$route_list_key], '|route_id:' . $route_id, $insertion_place, 0);
                            $exist_route_ids[] = $route_id;
                            $now_route_ids[] = $route_id;
                        }
                    }else{
                        $temp_route = explode('|',$route_list[$route_list_key]);
                        foreach($temp_route as $v){
                            if(substr_count($v, 'route_id:') != 0){
                                $route_id = trim(explode(':',$v)[1]);
                            }
                            if($route_id > 0){
                                break;
                            }
                        }
                    }

                    $add_permission[] = [
                        'name' => $permission_name,
                        'code' => $code,
                        'description' => $permission_name,
                        'parent_permission_name' => $parent_permission_name,
                        'route_id' => $route_id,
                        'menu_id' => $menu_id,
                    ];

                } else {
                    $now_route_ids[] = $route_id;
                    $is_update = 0;
                    if ($permission_list[$route_id]->count() == 1) { // 原非资源路由
                        $old_data = $permission_list[$route_id][0];
                        if ($permission_name != $old_data['name'] || $code != $old_data['code'] || !isset($parents[$parent_permission_name]) || $parents[$parent_permission_name] != $old_data['parent_id'] || !empty($resource) || ($menu_id > 0 && ((isset($menu_list[$old_data['id']]) && $menu_list[$old_data['id']][0]['admin_menu_id'] != $menu_id) || !isset($menu_list[$old_data['id']])))) {
                            $is_update = 1;
                        }
                    } else { // 原资源路由
                        if (empty($resource)) { // 当前非资源路由，一定需要修改
                            $is_update = 2;
                        } else { // 当前非资源路由，分情况修改
                            $old_data = [];
                            $permission_list[$route_id]->each(function ($item) use (&$old_data, $resource) {
                                if (explode('@', $item->code)[1] == $resource) {
                                    $old_data = $item;
                                }
                            });

                            if (empty($old_data)) { // 原不存在该权限
                                $is_update = 3;
                            } else { // 原已存在该权限
                                if ($permission_name != $old_data['name'] || $code != $old_data['code'] || !isset($parents[$parent_permission_name]) || $parents[$parent_permission_name] != $old_data['parent_id'] || ($menu_id > 0 && ((isset($menu_list[$old_data['id']]) && $menu_list[$old_data['id']][0]['admin_menu_id'] != $menu_id) || !isset($menu_list[$old_data['id']])))) {
                                    $is_update = 2;
                                }
                            }
                        }
                    }

                    if (($is_update == 1 && !empty($resource)) || ($is_update == 2 && empty($resource))) { // 如果存在资源路由与非资源路由的转换，删掉原来的，添加新的
                        if (!in_array($old_data['route_id'], $delete_permission_route_ids)) {
                            $delete_permission_route_ids[] = $old_data['route_id'];
                        }

                        $add_permission[] = [
                            'name' => $permission_name,
                            'code' => $code,
                            'description' => $permission_name,
                            'parent_permission_name' => $parent_permission_name,
                            'route_id' => $route_id,
                            'menu_id' => $menu_id,
                        ];
                    } else if ($is_update == 1 || $is_update == 2) {
                        $update_permission[] = [
                            'id' => $old_data->id,
                            'name' => $permission_name,
                            'code' => $code,
                            'description' => $permission_name,
                            'parent_permission_name' => $parent_permission_name,
                            'parent_id' => $old_data['parent_id'],
                            'route_id' => $route_id,
                            'menu_id' => $menu_id,
                        ];
                    } else if ($is_update == 3) {
                        $add_permission[] = [
                            'name' => $permission_name,
                            'code' => $code,
                            'description' => $permission_name,
                            'parent_permission_name' => $parent_permission_name,
                            'route_id' => $route_id,
                            'menu_id' => $menu_id,
                        ];
                    }
                }
            }
        }

        foreach ($exist_route_ids as $value) {
            if (!in_array($value, $now_route_ids)) {
                $delete_permission_route_ids[] = $value;
            }
        }

        \DB::beginTransaction();
        // 需要删除的权限
        if (!empty($delete_permission) || !empty($delete_permission_route_ids)) {

            if (!empty($delete_permission_route_ids)) {
                $delete_permission_list = \App\Model\AdminPermissionModel::whereIn('route_id', $delete_permission_route_ids)
                    ->where('is_on', 1)
                    ->select(['id'])
                    ->get();

                $delete_permission = array_merge($delete_permission, $delete_permission_list->pluck('id')->toArray());
            }

            $res = \App\Model\AdminPermissionModel::where('id', $delete_permission)
                ->update(['is_on' => 0]);
            if (!$res) {
                \DB::rollBack();
                throw new ApiException('数据库错误！');
            }

            // 清除多余的菜单关联
            \App\Model\AdminPermissionMenuModel::whereIn('admin_permission_id', $delete_permission)
                ->delete();
        }

        // 需要修改的权限
        if (!empty($update_permission)) {
            foreach ($update_permission as $value) {
                if (!isset($parents[$value['parent_permission_name']]) || $parents[$value['parent_permission_name']] != $value['parent_id']) {
                    // 如果权限组名有改变，或且目前不存在，直接添加新的
                    $parent_permission_model = new \App\Model\AdminPermissionModel();
                    set_save_data($parent_permission_model, [
                        'name' => $value['parent_permission_name'],
                        'description' => $value['parent_permission_name'],
                        'level' => 1
                    ]);
                    $res = $parent_permission_model->save();

                    if (!$res) {
                        \DB::rollBack();
                        throw new ApiException('数据库错误');
                    }
                    $parents[$value['parent_permission_name']] = $parent_permission_model->id;

                    // 记录原来的权限组
                    if (!in_array($value['parent_id'], $update_permission_parent_ids)) {
                        $update_permission_parent_ids[] = $value['parent_id']; // 记录当前修改掉的权限组
                    }
                }

                $res = \App\Model\AdminPermissionModel::where('id', $value['id'])
                    ->update(['name' => $value['name'], 'description' => $value['description'], 'code' => $value['code'], 'parent_id' => $parents[$value['parent_permission_name']]]);

                if (!$res) {
                    \DB::rollBack();
                    throw new ApiException('数据库错误！');
                }

                $num++;
                $menu_id = $value['menu_id'];
                // 判断是否需要绑定菜单
                if ($menu_id != 0) { // 先删除原有的再添加新的
                    \App\Model\AdminPermissionMenuModel::where('admin_permission_id', $value['id'])
                        ->delete();

                    $permission_menu_model = new \App\Model\AdminPermissionMenuModel();
                    set_save_data($permission_menu_model, [
                        'admin_permission_id' => $value['id'],
                        'admin_menu_id' => $menu_id
                    ]);
                    $res = $permission_menu_model->save();
                    if (!$res) {
                        \DB::rollBack();
                        throw new ApiException('数据库错误3');
                    }
                }
            }
        }

        // 需要新增的权限
        if (!empty($add_permission)) {
            foreach ($add_permission as $value) {
                // 判断权限组是否存在，不存在则创建
                if (!isset($parents[$value['parent_permission_name']])) {
                    $parent_permission = \App\Model\AdminPermissionModel::where('name', $value['parent_permission_name'])
                        ->where('is_on', 1)
                        ->first(['id']);

                    if ($parent_permission) {
                        $parents[$value['parent_permission_name']] = $parent_permission['id'];
                    } else {
                        $parent_permission_model = new \App\Model\AdminPermissionModel();
                        set_save_data($parent_permission_model, [
                            'name' => $value['parent_permission_name'],
                            'description' => $value['parent_permission_name'],
                            'level' => 1
                        ]);
                        $res = $parent_permission_model->save();

                        if (!$res) {
                            \DB::rollBack();
                            throw new ApiException('数据库错误');
                        }
                        $parents[$value['parent_permission_name']] = $parent_permission_model->id;
                    }
                }

                $parent_id = $parents[$value['parent_permission_name']];

                // 添加权限数据
                $permission_model = new \App\Model\AdminPermissionModel();
                set_save_data($permission_model, [
                    'name' => $value['name'],
                    'code' => $value['code'],
                    'description' => $value['description'],
                    'parent_id' => $parent_id,
                    'level' => 2,
                    'route_id' => $value['route_id'],
                ]);
                $res = $permission_model->save();
                if (!$res) {
                    \DB::rollBack();
                    throw new ApiException('数据库错误2');
                }
                $num++;
                // 判断是否需要绑定菜单
                $menu_id = $value['menu_id'];
                if ($menu_id != 0) {
                    $permission_menu_model = new \App\Model\AdminPermissionMenuModel();
                    set_save_data($permission_menu_model, [
                        'admin_permission_id' => $permission_model->id,
                        'admin_menu_id' => $menu_id
                    ]);
                    $res = $permission_menu_model->save();
                    if (!$res) {
                        \DB::rollBack();
                        throw new ApiException('数据库错误3');
                    }
                }
            }
        }

        // 写入admin.php
        if (!empty($route_list)) {

            foreach ($route_list as $key => $value) {
                $route_file_arr[$key] = $value;
            }

            $res = file_put_contents(base_path('routes/admin.php'), implode('', $route_file_arr));

            if (!$res) {
                \DB::rollBack();
                throw new ApiException('写入admin.php错误！');
            }
        }

        \DB::commit();

        // 清除多余的权限组
        $permission_list = \App\Model\AdminPermissionModel::whereIn('parent_id', $update_permission_parent_ids)
            ->where('is_on', 1)
            ->select(['id', 'parent_id'])
            ->get();

        if ($permission_list->isEmpty()) {
            $exist_parent_ids = [];
        } else {
            $exist_parent_ids = $permission_list->pluck('parent_id')->toArray();
        }

        $delete_permission_parent = array_diff($update_permission_parent_ids, $exist_parent_ids);

        if ($delete_permission_parent) {
            $res = \App\Model\AdminPermissionModel::whereIn('id', $delete_permission_parent)
                ->update(['is_on' => 0]);
            if (!$res) {
                throw new ApiException('清除多余权限组错误！');
            }
        }

        $this->info('成功更改权限' . $num . '个');
        if (!empty($lack_menu_list)) {
            $this->warn('缺少以下菜单，请手动添加:' . PHP_EOL . implode(PHP_EOL, $lack_menu_list));
        }

    }

    /**
     * 验证bool类型参数
     * @param $as
     * @param string|bool $value 需要验证的值
     * @return bool
     * @throws ApiException
     */
    public function verifyBool($as, $value)
    {
        if (!is_bool($value) && $value != "false" && $value != "true") {
            throw new ApiException('create格式错误：' . $as);
        }

        if (is_bool($value)) {
            return $value;
        }

        if ($value == "false") {
            return false;
        }

        if ($value == "true") {
            return true;
        }
    }

    /**
     * 验证命名规范
     * @param $value
     * @param $value2
     * @param $uri
     * @param $methods
     * @return bool
     */
    public function verifyAs($value, $value2, $uri, $methods)
    {
        $route_array = explode('/', $uri);
        $length = count($route_array);
        switch ($value) {
            case 'index':
                if (in_array('GET', $methods) && $value2 === $route_array[$length - 1]) {
                    return true;
                }
                break;
            case 'store':
                if (in_array('POST', $methods) && $value2 === $route_array[$length - 1]) {
                    return true;
                }
                break;
            case 'create':
                if (in_array('GET', $methods) && $value2 === $route_array[$length - 2] && $route_array[$length - 1] === 'create') {
                    return true;
                }
                break;
            case 'show':
                if (in_array('GET', $methods) && $value2 == $route_array[$length - 2] && strpos($route_array[$length - 1], '{') === 0 && strpos($route_array[$length - 1], '}') === strlen($route_array[$length - 1]) - 1) {
                    return true;
                }
                break;
            case 'edit':
                if (in_array('GET', $methods) && $value2 == $route_array[$length - 3] && strpos($route_array[$length - 2], '{') === 0 && strpos($route_array[$length - 2], '}') === strlen($route_array[$length - 2]) - 1 && $route_array[$length - 1] === 'edit') {
                    return true;
                }
                break;
            case 'update':
                if (in_array('PUT', $methods) && $value2 == $route_array[$length - 2] && strpos($route_array[$length - 1], '{') === 0 && strpos($route_array[$length - 1], '}') === strlen($route_array[$length - 1]) - 1) {
                    return true;
                }
                break;
            case 'destroy':
                if (in_array('DELETE', $methods) && $value2 == $route_array[$length - 2] && strpos($route_array[$length - 1], '{') === 0 && strpos($route_array[$length - 1], '}') === strlen($route_array[$length - 1]) - 1) {
                    return true;
                }
                break;
            default:
                false;
                break;
        }
    }

    /**
     * 获取route_id
     * @param array $exist_route_ids 已存在的route_id
     * @return int
     */
    public function getRouteId($exist_route_ids)
    {
        while (true) {
            $route_id = rand(1, 99999);
            if (!in_array($route_id, $exist_route_ids)) {
                return $route_id;
            }
        }
    }
}
