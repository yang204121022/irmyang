<?php

namespace Irmyang\Database;

use Exception;
use Irmyang\Database\Table;
use Irmyang\Database\Base;
use Irmyang\Database\LinkageAttribute;

class TableField extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'irm_yang_table_field';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $is_sort = true;
    /**
     *  获取字段列表
     *
     * @access    public
     * @param  object  $object 对象
     * @return    array|boolean
     */
    function getFieldList($object='')
    {
        $table_name=$object->getTable();
        $table_db=new Table;
        $connection=$this->getConnectionName();
        $table_db=$table_db->setConnection($connection);

        if(!$table_db->isTable($table_name,$object))
        {
            $this->setAttributes('msg',$table_db->msg);
            return false;
        }
        $table_field_lists =$this->getSchema($table_name);
        //检测是否有新增字段,如果有新增字段自动创建
        $lists=$object->getTableField();
        $default_lists=[];
        foreach($lists as $v)
        {
            if(empty($table_field_lists[$v['field']]))
            {
                if(!$this->add($table_name,$v)) return false;
            }
            $default_lists[$v['field']]=$v;
        }
        //字段表
        $table=$this->getTable();
        if(!$table_db->isTable($table,$this))
        {
            $this->setAttributes('msg',$table_db->msg);
            return false;
        }

        $lists=$this->where('table_name',$table_name)->orderBy('sort','ASC')->get();
        if($lists)
        {
            foreach($lists as $k=>$v)
            {
                if(empty($table_field_lists[$v->field]))
                {
                    $v->delete();
                    unset($lists[$k]);
                }
                else
                {
                    unset($table_field_lists[$v->field]);
                }
            }
        }
        if($table_field_lists)
        {
            foreach($table_field_lists as $field=>$v)
            {
                $db=clone $this;
                $db->table_name=$table_name;//表
                $db->field=$field;//字段
                if(isset($default_lists[$field]))
                {
                    $db->field_type=$default_lists[$field]['field_type'];
                    $db->comment=$default_lists[$field]['comment'];
                    if(!empty($default_lists[$field]['field_length']))  $db->field_length=$default_lists[$field]['field_length'];
                    if(!empty($default_lists[$field]['default']))  $db->field_default=$default_lists[$field]['default'];
                    if(!empty($default_lists[$field]['field_decimal']))  $db->field_decimal=$default_lists[$field]['field_decimal'];
                    if(!empty($default_lists[$field]['model']))  $db->model=$default_lists[$field]['model'];
                    if(!empty($default_lists[$field]['model_field']))  $db->model_field=$default_lists[$field]['model_field'];
                    if(!empty($default_lists[$field]['null']))  $db->null=$default_lists[$field]['null'];
                    if(!empty($default_lists[$field]['admin_list_show']))  $db->admin_list_show=$default_lists[$field]['admin_list_show'];
                    if(!empty($default_lists[$field]['admin_list_fold']))  $db->admin_list_fold=$default_lists[$field]['admin_list_fold'];
                    if(!empty($default_lists[$field]['admin_list_custom']))  $db->admin_list_custom=$default_lists[$field]['admin_list_custom'];
                    if(!empty($default_lists[$field]['admin_footer_edit']))  $db->admin_list_show=$default_lists[$field]['admin_footer_edit'];
                    if(!empty($default_lists[$field]['admin_search']))  $db->admin_search=$default_lists[$field]['admin_search'];
                    if(!empty($default_lists[$field]['admin_list']))  $db->admin_list=$default_lists[$field]['admin_list'];
                    if(!empty($default_lists[$field]['admin_add_show']))  $db->admin_add_show=$default_lists[$field]['admin_add_show'];
                    if(!empty($default_lists[$field]['admin_edit_show']))  $db->admin_edit_show=$default_lists[$field]['admin_edit_show'];
                    if(!empty($default_lists[$field]['admin_add']))  $db->admin_add=$default_lists[$field]['admin_add'];
                    if(!empty($default_lists[$field]['admin_edit']))  $db->admin_edit=$default_lists[$field]['admin_edit'];
                }
                else
                {
                    $db->field_type=$v->DATA_TYPE;//数据类型
                    $db->comment=$v->COLUMN_COMMENT;//备注
                    $db->field_default=$v->COLUMN_DEFAULT;//默认值
                    switch($v->DATA_TYPE)
                    {
                        case 'int':
                            $db->field_length=$v->NUMERIC_PRECISION;//长度
                            break;
                        case 'varchar':
                            $db->field_length=$v->CHARACTER_MAXIMUM_LENGTH;//长度
                            break;
                    }
                    $db->admin_list_show=1;//列表展示
                    $db->admin_edit=1;//修改
                }
                $db->save();
            }
            $lists=$this->where('table_name',$table_name)->orderBy('sort','ASC')->get();
        }

        if(count($lists)>0)
        {
            foreach($lists as $k=>$v)
            {
                switch($v->field_type)
                {
                    case 'select':
                    case 'radio':
                    case 'checkbox':
                    case 'linkage':
                        $attr_lists=LinkageAttribute::select(['id','parent_id','title'])->where('linkage_id',$v->linkage_id)->orderBy('sort','ASC')->get();
                        $v->value_lists=LinkageAttribute::listsToArray($attr_lists);
                        break;
                }
                $v=$object->addExtrFields($v);
                $lists[$k]=$v;
            }
        }
        return $lists;
    }
    /**
     * 检查表名是否合法
     * @param string $table
     * @return string
     * @throws Exception
     */
    public function checkTableName(string $table): string
    {
        if (!preg_match('/^[a-zA-Z_0-9]+$/', $table)) {
            throw new Exception('表名不合法');
        }
        return $table;
    }
    /**
     * 按表获取摘要
     * @param $table
     * @param null $section
     * @return array|mixed
     * @throws Exception
     */
    function getSchema($table){
        $table=$this->checkTableName($table);
        $database=$this->getConnection()->getDatabaseName();
        $_lists = $this->getConnection()->select("select * from information_schema.COLUMNS where TABLE_SCHEMA='$database' and table_name = '$table' order by ORDINAL_POSITION");
        if(!$_lists) throw new Exception($table.'表字段不存在');
        $lists=[];
        foreach($_lists as $v)
        {
            $lists[$v->COLUMN_NAME]=$v;
        }
        return $lists;
    }
    /**
     * @title 添加字段
     *
     * @access    public
     * @param  string $table 表
     * @param  array $item 字段属性
     * @return    boolean
     */
    function add($table=null,$item=[])
    {
        $query=$this->getQuery($item);
        if(!$query) return false;
        $query=' ALTER TABLE `'.$table.'` ADD  '.$query;
        if(!$this->getConnection()->statement($query))
        {
            $this->setAttributes('msg','添加字段： '.$table.'  失败，原因是：'.$query);
            return false;
        }
        return true;
    }
    /**
     * @title 修改字段
     *
     * @access    public
     * @param  object $model 模块
     * @return    boolean
     */
    function editField($model=null)
    {
        $table=$this->getConnection()->getTablePrefix() . $model->getTable();
        $item=json_decode(json_encode($this), true);
        $query=$this->getQuery($item);
        if(!$query) return false;

        $query=' ALTER TABLE `'.$table.'` MODIFY '.$query;
        if(!$this->getConnection()->statement($query))
        {
            $this->setAttributes('msg','修改字段： '.$table.'  失败，原因是：');
            return false;
        }
        return true;
    }
    /**
     * @title 删除字段
     *
     * @access    public
     * @param  object $model 模块
     * @return boolean
     */
    function dropField($model=null)
    {
        $table=$this->getConnection()->getTablePrefix() . $model->getTable();
        $query=' ALTER TABLE `'.$table.'` DROP  `'.$this->field.'`';
        if(!$this->getConnection()->statement($query))
        {
            $this->setAttributes('msg','删除字段： '.$table.'  失败，原因是：');
            return false;
        }
        return $this->delete();
    }
    /** @title 拼接字段
     *
     * @param     array  $item  字段信息
     * @return    string|boolean
     */
    function getQuery($item=[])
    {
        $field=$item['field'];
        if(!$field)
        {
            $this->setAttributes('msg','字段不能为空');
            return false;
        }

        $field_length=empty($item['field_length']) ? 10 : intval($item['field_length']);//长度
        $field_decimal=empty($item['field_decimal']) ? 0 : intval($item['field_decimal']);//小数位
        $null=isset($item['null']) ? ($item['null']==1 ? 'NULL':'NOT NULL'):'NULL';//是否允许为空
        $default=isset($item['default']) ? $item['default'] : '';//默认值
        $comment=$item['comment'];//注释

        if(!$comment)
        {
            $this->setAttributes('msg','注释不能为空');
            return false;
        }

        $query='`'.$field.'` ';
        switch($item['field_type'])
        {
            case 'varchar'://字符
            case 'checkbox'://多选框
            case 'modular-more'://模块多联
            case 'thumb'://缩略图
            case 'file'://文件
            case 'video'://视频
            case 'icon'://图标
            case 'linkage'://联动模型
            case 'book_grade'://课本章节
                $field_length or $field_length=200;
                $query.='varchar('.$field_length.') ';
                break;
            case 'int'://数字
            case 'switch'://切换
            case 'select'://下来框
            case 'radio'://单选框
            case 'modular-file'://模块
            case 'datetime'://时间
                $default=intval($default);
                $default or $default=0;
                $field_length or $field_length=10;
                $query.='int('.$field_length.') ';
                break;
            case 'tinyint'://从 0 到 255 的整型数据。存储大小为 1 字节
                $default=intval($default);
                $default or $default=0;
                $field_length=1;
                $query.=$item['field_type'].'('.$field_length.') ';
                break;
            case 'float'://浮点型
                $default=floatval($default);
                $default or $default=0;
                $field_decimal or $field_decimal=2;
                $field_length or $field_length=6;

                $query.=$item['field_type'].'('.$field_length.','.$field_decimal.') ';
                break;
            case 'images'://多图
            case 'text'://文本
                $query.='text ';
                $default='';
                break;
            case 'fulltext'://文本
                return 'FULLTEXT ('.$field.')';
                break;
            default:
                throw new Exception('字段"'.$item['field'].'"类型不存在'.$item['field_type']);
                break;
        }
        //自动增长
        if(!empty($item['auto_increment']))
        {
            $query.='AUTO_INCREMENT ';
        }
        else
        {
            if($default!=='') $query.='DEFAULT \''.$default.'\' ';
            $query.=$null.' ';
        }
        $query.='COMMENT \''.$comment.'\'';
        return $query;
    }
    /**
     * @title 获取字段值类型
     *
     * @access    public
     * @param     string  $table  表
     * @param     string  $field  字段
     * @return    array
     */
    function getValueTypeList($type='')
    {
        $lists=[];
        $lists['varchar']=['title'=>'字符串'];
        $lists['int']=['title'=>'数字'];
        $lists['float']=['title'=>'小数'];
        $lists['switch']=['title'=>'布尔型'];
        $lists['text']=['title'=>'文本'];
        $lists['mobile']=['title'=>'手机'];
        $lists['email']=['title'=>'邮箱'];
        $lists['baidu_edit']=['title'=>'百度编辑器'];
        $lists['select']=['title'=>'下拉框'];
        $lists['radio']=['title'=>'单选框'];
        $lists['checkbox']=['title'=>'复选框'];
        $lists['datetime']=['title'=>'时间'];
        $lists['thumb']=['title'=>'缩略图'];
        $lists['images']=['title'=>'多图'];
        $lists['file']=['title'=>'文件'];
        $lists['icon']=['title'=>'图标'];
        $lists['video']=['title'=>'视频'];
        $lists['modular-file']=['title'=>'模块'];
        $lists['linkage']=['title'=>'联动模型'];
        $lists['book_grade']=['title'=>'课本章节'];
        $lists['modular-more']=['title'=>'模块多联'];
        if($type=='array')
        {
            $list=[];
            foreach($lists as $k=>$v)
            {
                $v['type']=$k;
                $list[]=$v;
            }
            return $list;
        }
        return $lists;
    }
    /**
     * @title 获取创建表格字段
     * @return array|object
     */
    function getTableField(){
        $field_lists=[];
        $field_lists[]=['field'=>'id','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'ID','field_type'=>'int','primary'=>true,'auto_increment'=>true];
        $field_lists[]=['field'=>'created_at','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'添加时间','field_type'=>'int'];
        $field_lists[]=['field'=>'updated_at','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'修改时间','field_type'=>'int'];
        $field_lists[]=['field'=>'table_name','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'表名称','field_type'=>'varchar'];
        $field_lists[]=['field'=>'is_delete','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'删除状态','field_type'=>'int'];
        $field_lists[]=['field'=>'field','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'字段','field_type'=>'varchar'];
        $field_lists[]=['field'=>'comment','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'备注','field_type'=>'varchar'];
        $field_lists[]=['field'=>'field_type','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'字段类型','field_type'=>'varchar'];
        $field_lists[]=['field'=>'value_type','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'字段类型','field_type'=>'varchar'];
        $field_lists[]=['field'=>'field_length','field_length'=>10,'field_decimal'=>0,'default'=>'','comment'=>'字段长度','field_type'=>'int'];
        $field_lists[]=['field'=>'field_decimal','field_length'=>10,'field_decimal'=>0,'default'=>'','comment'=>'小数位长度','field_type'=>'int'];
        $field_lists[]=['field'=>'field_default','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'默认值','field_type'=>'varchar'];
        $field_lists[]=['field'=>'model','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'模块','field_type'=>'varchar'];
        $field_lists[]=['field'=>'model_field','field_length'=>200,'field_decimal'=>0,'default'=>'','comment'=>'模块字段','field_type'=>'varchar'];
        $field_lists[]=['field'=>'linkage_id','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'联动字段','field_type'=>'int'];
        $field_lists[]=['field'=>'is_multiple','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'多选','field_type'=>'int'];

        $field_lists[]=['field'=>'null','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'允许空','field_type'=>'switch'];
        $field_lists[]=['field'=>'admin_list_show','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'列表展示','field_type'=>'switch'];
        $field_lists[]=['field'=>'admin_list_fold','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'折叠展示','field_type'=>'switch'];
        $field_lists[]=['field'=>'admin_list_custom','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'自定义','field_type'=>'switch'];
        $field_lists[]=['field'=>'admin_footer_edit','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'底部修改','field_type'=>'switch'];

        $field_lists[]=['field'=>'admin_search','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'搜索','field_type'=>'switch'];
        $field_lists[]=['field'=>'admin_list','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'列表修改','field_type'=>'switch'];
        $field_lists[]=['field'=>'admin_add_show','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'添加展示','field_type'=>'switch'];
        $field_lists[]=['field'=>'admin_edit_show','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'修改展示','field_type'=>'switch'];
        $field_lists[]=['field'=>'admin_add','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'添加修改','field_type'=>'switch'];
        $field_lists[]=['field'=>'admin_edit','field_length'=>2,'field_decimal'=>0,'default'=>0,'comment'=>'修改修改','field_type'=>'switch'];
        $field_lists[]=['field'=>'sort','field_length'=>10,'field_decimal'=>0,'default'=>0,'comment'=>'排序','field_type'=>'int'];
        return $field_lists;
    }
}
