<?php

namespace Irmyang\Database;

use App\Models\DataBase\LinkageAttribute as ModelLinkageAttribute;
use support\Cache;
use Irmyang\Database\TableField;
use Irmyang\Database\Base;

class Linkage extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'irm_yang_linkage';


    /**获取联动数组
     *
     * @param     int  type  联动名称
     * @param     bool  update  更新
     * @return    array
     */

    public function getArrayByType($field,$is_update=false)
    {
        $key='system_linkage_'.$field.'_array';
        if(!$is_update)
        {
            if(isset(self::$type_arrays[$key])) return self::$type_arrays[$key];
            self::$type_arrays[$key]=Cache::get($key);
            if(self::$type_arrays[$key]) return self::$type_arrays[$key];
        }
        $item=$this->getGradeByType($field,$is_update);
        if(!$item) return false;
        $item->setAttributes('son_lists',gradeToArray($item->son_lists));
        self::$type_arrays[$key]=$item;
        Cache::put($key,self::$type_arrays[$key]);
        return self::$type_arrays[$key];
    }
    /**获取联动数组
     *
     * @param     int  type  联动名称
     * @param     bool  update  更新
     * @return    array
     */

    public function getGradeByType($field,$update=false)
    {
        if(!$field)
        {
            $this->setAttributes('msg','字段不能为空');
            return false;
        }
        $key='system_linkage_'.$field.'_grade';
        if(!$update)
        {
            if(isset(self::$type_grades[$key])) return self::$type_grades[$key];
            self::$type_grades[$key]=Cache::get($key);
            if(self::$type_grades[$key]) return self::$type_grades[$key];
        }
        $item=self::select(['id','title','field'])->where('field','=',$field)->first();
        if(!$item)
        {
            $this->setAttributes('msg','不存在');
            return false;
        }
        $son_lists=ModelLinkageAttribute::select(['id','title','parent_id'])->where('linkage_id',$item->id)->orderBy('sort','asc')->orderBy($this->parent_key,'asc')->get();
        $item->setAttributes('son_lists',listsToGrade($son_lists));
        self::$type_grades[$key]=$item;
        Cache::put($key,self::$type_grades[$key]);
        return self::$type_grades[$key];
    }
    /**@title 获取联动父类
     *
     * @param     string  $type  联动名称
     * @param     int  $id  ID
     * @return    string
     */
    function getParent($type,$id=0)
    {
        if(!$id) return [0];
        $item=$this->getGradeByType($type);
        return $this->getParentLoop($item->son_lists,$id);
    }
    /**@title 联动父类循环
     *
     * @param     array  $lists  联动数组
     * @param     int  $id  id
     * @return    array
     */
    private function getParentLoop($lists,$id){
        $vals=[];
        $id or $id=0;
        $vals[]=$id;
        if(!$id) return $vals;
        $key=$this->parent_key;
        if(isset($lists[$id]->$key))
        {
            $vals=array_merge($this->getParentLoop($lists,$lists[$id]->$key),$vals);
        }
        return $vals;
    }
    /**@title 联动子类
     *
     * @param  string  type  联动名称
     * @param int  id  id
     * @return    string
     */
    function getSon($type,$id=0)
    {
        if(!$id)
        {
            $this->setAttributes('msg','id不能为空');
            return false;
        }
        $item=$this->getGradeByType($type);
        if(!$item) return false;
        $vals=[$id];
        $this->getSonLoop($item['son_lists'],$id,$vals);
        return $vals;
    }
    /**@title 联动父类循环
     *
     * @param     array  $lists  联动数组
     * @param     int  $id  id
     * @return    array
     */
    private function getSonLoop($lists,$id,&$vals){
        if(isset($lists[$id]->son_lists))
        {
            foreach($lists[$id]->son_lists as $k)
            {
                $vals[]=$k;
                $this->getSonLoop($lists,$k,$vals);
            }
        }
    }
    /**@title 获取属性
     *
     * @param  string  type  联动名称
     * @param int  id  id
     * @return    string
     */
    function getattrByIds($data=[],$keys=[])
    {
        foreach($keys as $field=>$item)
        {
            $item_lists=$this->getGradeByType($field);
            $key=isset($item['key']) ? $item['key'] : $field;
            $name=isset($item['name']) ? $item['name'] : $field.'_title';
            if(empty($data->$key)) continue;
            if(!empty($item_lists->son_lists[$data->$key]))
            {
                $data->setAttributes($name,$item_lists->son_lists[$data->$key]->title);
            }
        }
        return $data;
    }
    /**@title 联动子类
     *
     * @param  string  type  联动名称
     * @param int  id  id
     * @return    string
     */
    function getRelevantAll($type,$id=0)
    {
        if(!$id)
        {
            $this->setAttributes('msg','id不能为空');
            return false;
        }
        $item=$this->getGradeByType($type);
        if(!$item) return false;
        $vals=$this->getParentLoop($item['son_lists'],$id);
        $this->getSonLoop($item['son_lists'],$id,$vals);
        return $vals;
    }
    /**@title 获取最有值，父类值全部删除
     *
     * @param  string  type  联动名称
     * @param array  val
     * @return    string
     */
    function getLastValue($type,$val='')
    {
        $values=explode(',',$val);
        $parent_ids=[];
        foreach($values as $v)
        {
            $par=$this->getParent($type,$v);
            array_pop($par);
            $parent_ids=array_merge($parent_ids,$par);
        }
        $values=array_diff($values,$parent_ids);
        sort($values);
        return implode(',',$values);
    }

    /**@title 根据值获取
     *
     * @param  string  type  联动名称
     * @param array  val
     * @return    string
     */
    function getValueByString($type,$title='')
    {
        $grade_lists=$this->getGradeByType($type)->son_lists;
        $val=0;
        foreach($grade_lists as $v)
        {
            if(!empty($v->title) && strstr($title,$v->title))
            {
                $val=$v->id;
                if(!empty($v->son_lists))
                {
                    $val=$this->getValueByStringLoop($grade_lists,$title,$val);
                }
            }
        }
        if(!$val) return false;
        $item=['val'=>$val];
        $item['parent_val']=$this->getParent($type,$val);
        $item['son_val']=$this->getSon($type,$val);
        return $item;
    }
    /**@title 根据值获取
     *
     * @param  string  type  联动名称
     * @param array  val
     * @return    string
     */
    function getValueMultipleByString($type,$title='',$grade_lists=[])
    {
        if(!$grade_lists) $grade_lists=$this->getGradeByType($type)->son_lists;
        if(!$grade_lists) return [];
        $lists=[];
        foreach($grade_lists as $v)
        {
            $vals=[];
            if(!empty($v->son_lists))
            {
                $vals=$this->getValueMultipleByString($type,$title,$v->son_lists);
            }
            if(!$vals && !empty($v->title) && strstr($title,$v->title))
            {
                $vals=$this->getParent($type,$v->id);
            }
            if($vals) $lists=array_merge($lists,$vals);
        }
        return $lists;
    }
    /**@title 根据值获取
     *
     * @param  string  type  联动名称
     * @param array  val
     * @return    string
     */
    private function getValueByStringLoop($lists,$title='',$val=0)
    {
        foreach($lists[$val]->son_lists as $v)
        {
            if(!empty($v->title) && strstr($title,$v->title))
            {
                $val=$v->id;
                if(!empty($v->son_lists))
                {
                    return $this->getValueByStringLoop($lists,$title,$val);
                }
            }
        }
        return $val;
    }
    /**
     * @title 获取创建表格字段
     * @return object
     */
    function getTableField(){
        $lists=[];
        $lists[]=['field'=>'id','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'ID','field_type'=>'int','primary'=>true,'auto_increment'=>true];
        $lists[]=['field'=>'title','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'标题','field_type'=>'varchar'];
        $lists[]=['field'=>'created_at','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'添加时间','field_type'=>'int'];
        $lists[]=['field'=>'updated_at','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'修改时间','field_type'=>'int'];
        return $lists;
    }
}