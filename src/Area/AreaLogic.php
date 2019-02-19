<?php
namespace JiaLeo\Laravel\Area;


trait AreaLogic
{

    /**
     * @param $data array 筛选数组
     *
     */
    public static function getAreaList($data)
    {
        $cache_list = \Cache::get('area:'.json_encode($data));
        if(!empty($cache_list)){
            return $cache_list;
        }

        $list = \App\Model\AreaModel::select(['id', 'parent_id', 'name', 'level', 'letter']);

        //筛选层级
        if (isset($data['level'])) {
            $list->where('level', $data['level']);
        }else if(isset($data['level_min'])&&isset($data['level_max'])){
            $list->whereBetween('level', [$data['level_min'],$data['level_max']]);
        }else{
            $list->whereBetween('level', [0,3]);
        }

        //筛选下级
        if (isset($data['parent_id'])) {
            $list->where('parent_id', $data['parent_id']);
        }

        //排序
        if (isset($data['order'])) {
            $list->orderBy($data['order']);
        }

        if (isset($data['is_paginate']) && $data['is_paginate'] == 1) {
            return $list->paginate();
        } else {

            $list=self::tree($list->get()->toArray());

            //缓存5分钟
            \Cache::add('area:'.json_encode($data),$list,60*5);

            return $list;
        }
    }

    /**
     * 返回树结构列表
     * @param array $items 地区列表
     * @param string $id_name 主键
     * @param string $pid_name 父级ID名称
     * @param string $children_name 子级名称
     * @return array
     */
    protected static function tree($items, $id_name = 'id', $pid_name = 'parent_id', $children_name = 'children')
    {
        $items_new = array();
        foreach ($items as $item) {
            $items_new[$item[$id_name]] = $item;
        }
        $items = $items_new;
        unset($items_new);
        $tree = array();
        foreach ($items as $item) {
            if (isset($items[$item[$pid_name]])) {
                $items[$item[$pid_name]][$children_name][] = &$items[$item[$id_name]];
            } else {
                $tree[] = &$items[$item[$id_name]];
            }
        }
        return $tree;
    }

    /**
     * 根据地区字母排序数组
     * @param $area_list
     * @param string $letter_name
     * @return array
     */
    public static function letter($area_list, $letter_name = 'letter')
    {
        $letterArray = array();
        if (!empty($area_list)) {
            foreach ($area_list as $key => $value) {
                $letter = $value[$letter_name];
                $area_list[$key]['letter'] = $letter;
            }
            usort($area_list, function ($a, $b) {
                $al = $a['letter'];
                $bl = $b['letter'];
                if ($al == $bl) return 0;
                return ($al < $bl) ? -1 : 1;
            });
            foreach ($area_list as $key => $value) {
                $letter = $value['letter'];
                unset($value['letter']);
                $letterArray[$letter]['letter'] = $letter;
                $letterArray[$letter]['letter_order'][] = $value;
            }
        }
        return array_values($letterArray);
    }
}