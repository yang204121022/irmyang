<?php

namespace Irmyang\Database;

use Irmyang\Database\TableField;
use Irmyang\Database\Base;

class LinkageAttribute extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'irm_yang_linkage_attribute';

    protected $is_parent = true;
    protected $is_sort = true;
    protected $parent_top_key='linkage_id';

    /**
     * @title 层级分组
     * @param array $lists 数组
     * @param string $parent_key 分组键值
     * @param string $key 键值
     * @return array  数组
     */
    public static function listsToGrade($lists,$parent_key='parent_id',$key='id')
    {
        $lists=json_decode(json_encode($lists));
        $result_lists=[];
        foreach($lists as $v)
        {
            $parent_value=$v->$parent_key ? $v->$parent_key : 0;
            if(isset($result_lists[$parent_value]))
            {
                $result_lists[$parent_value]->son_lists[]=$v->$key;
            }
            else
            {
                $result_lists[$parent_value]=new \StdClass();
                $result_lists[$parent_value]->son_lists[]=$v->$key;
            }

            if(isset($result_lists[$v->$key]))
            {
                $v->son_lists=$result_lists[$v->$key]->son_lists;
            }
            $result_lists[$v->$key]=$v;
        }
        return $result_lists;
    }

    /**
     * @title 层级分组
     * @param array|object $lists 数组
     * @param string $parent_key 分组键值
     * @param string $key 键值
     * @return array  数组
     */
    public static function listsToArray($lists,$parent_key='parent_id',$key='id',$parent_id=0){
        if($parent_id==0)
        {
            if(!$lists) return [];
            $lists=self::listsToGrade($lists,$parent_key,$key);
            if(!isset($lists[$parent_id])) return array_values($lists);
        }
        return self::gradeToArray($lists,$parent_key,$key);
    }
    /**
     * @title 层级结构转数组结构
     * @param array $lists 数组
     * @param string $parent_key 分组键值
     * @param string $key 键值
     * @return array  数组
     */
    public static function gradeToArray($lists,$parent_key='parent_id',$key='id',$parent_id=0){
        $result_lists=[];
        if(empty($lists[$parent_id]->son_lists)) return [];
        foreach($lists[$parent_id]->son_lists as $v)
        {
            $item=$lists[$v];
            if(isset($item->son_lists))
            {
                $item->son_lists=self::gradeToArray($lists,$parent_key,$key,$v);
            }
            $result_lists[]=$item;
        }
        return $result_lists;
    }

    /**
     * @title 获取创建表格字段
     * @return object
     */
    function getTableField(){
        $lists=[];
        $lists[]=['field'=>'id','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'ID','field_type'=>'int','primary'=>true,'auto_increment'=>true];
        $lists[]=['field'=>'linkage_id','field_length'=>10,'field_decimal'=>0,'default'=>0,'model'=>'Irmyang\Database\Linkage','model_field'=>'title','comment'=>'类型','field_type'=>'modular-file'];
        $lists[]=['field'=>'parent_id','field_length'=>10,'field_decimal'=>0,'default'=>0,'model'=>'Irmyang\Database\LinkageAttribute','model_field'=>'title','comment'=>'上级','field_type'=>'modular-file'];
        $lists[]=['field'=>'sort','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'排序','field_type'=>'int'];

        $lists[]=['field'=>'title','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'标题','field_type'=>'varchar'];
        $lists[]=['field'=>'created_at','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'添加时间','field_type'=>'int'];
        $lists[]=['field'=>'updated_at','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'修改时间','field_type'=>'int'];
        return $lists;
    }
}