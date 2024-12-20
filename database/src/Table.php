<?php

namespace Irmyang\Database;

use Irmyang\Database\TableField;
use Irmyang\Database\Base;

class Table extends Base
{
    /** @title 检测表是否存在
     *
     * @param     string  $table  表
     * @return    boolean|array
     */
    function isTable($table='',$object='')
    {
        if(!$table)
        {
            $this->setAttributes('msg',$table.'表不能为空1');
            return false;
        }
        $lists=$this->getList($table);
        if(!$lists)
        {
            if(!$object || !method_exists($object,'getTableField'))
            {
                $this->setAttributes('msg',$table.'表不能为空2');
                return false;
            }
            $field_lists=$object->getTableField();
            return $this->create($table,$field_lists);
        }
        return $lists;
    }
    /**
     * @title 创建表
     *
     * @access    public
     * @param     string  $table  表
     * @param     array  $lists  字段列表
     * @return    boolean
     */
    function create($table,$lists=[])
    {
        if($this->isTable($table)) return true;

        $engine='InnoDB';
        $query='show variables like \'default_storage_engine\'';
        $engine_lists=$this->getConnection()->select($query);
        if($engine_lists)
        {
            $engine=$engine_lists[0]->Value;
        }

        $query='';
        $primary='';
        $table_field_db=new TableField();
        $connection=$this->getConnectionName();
        $table_field_db->setConnection($connection);

        foreach($lists as $k=>$v)
        {
            $field_query=$table_field_db->getQuery($v);
            if(!$field_query)
            {
                $this->setAttributes('msg',$table_field_db->msg);
                return false;
            }
            $query.=($query?",\n":"").$field_query;
            if(!empty($v['primary'])) $primary='PRIMARY KEY (`'.$v['field'].'`)';
        }

        $query='CREATE TABLE `'.$table.'`('.$query;
        if($primary) $query.=",\n ".$primary;


        $query.=") \n ";
        $query.='ENGINE='.$engine.' AUTO_INCREMENT=1 DEFAULT CHARSET=utf8';

        if(!$this->getConnection()->statement($query))
        {
            $this->setAttributes('msg','创建表： '.$table.'  失败，原因是：');
            return false;
        }
        return true;
    }
    /**
     * @title 删除表
     *
     * @access    public
     * @param     string  $table  表
     * @return    bool
     */
    function drop($table)
    {
        if(!$this->isTable($table)) return true;
        $query='DROP TABLE `'.$table.'` ';
        if(!$this->getConnection()->statement($query))
        {
            $this->setAttributes('msg','删除表： '.$table.'  失败，原因是：');
            return false;
        }
        return true;
    }
    /**
     * @title 获取数据库表
     *
     * @access    public
     * @param     string  $keyword  表
     * @return    array
     */
    function getList($keyword='')
    {
        $query='SHOW TABLES';
        if($keyword) $query.=' LIKE \''.$keyword.'\'';
        $lists=$this->getConnection()->select($query);
        if(!$lists) $lists=[];
        return $lists;
    }
    /**
     * @title 优化表
     *
     * @access    public
     * @param     string  $table  表
     * @return    bool
     */
    function optimize($table)
    {
        if(!$this->isTable($table)) return false;
        $query='OPTIMIZE TABLE `'.$table.'`';
        if(!$this->getConnection()->statement($query))
        {
            $this->setAttributes('msg','执行优化表： '.$table.'  失败，原因是：');
            return false;
        }
        return true;
    }
    /**
     * @title 修复表
     *
     * @access    public
     * @param     string  $table  表
     * @return    bool
     */
    function repair($table)
    {
        if(!$this->isTable($table)) return false;
        $query='REPAIR TABLE `'.$table.'` ';
        if(!$this->getConnection()->statement($query))
        {
            $this->setAttributes('msg','修复表： '.$table.'  失败，原因是：');
            return false;
        }
        return true;
    }
    /**
     * @title 修改表名称
     *
     * @access    public
     * @param     string  $table  表
     * @return    bool
     */
    function rename($table,$new_table)
    {
        if(!$this->isTable($table)) return false;
        $query='RENAME TABLE `'.$table.'` TO '.$new_table;
        if(!$this->getConnection()->statement($query))
        {
            $this->setAttributes('msg','修改表名失败');
            return false;
        }
        return true;
    }
}