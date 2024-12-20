<?php

namespace Irmyang\Database;


use Webman\Http\Request;
use Webman\Http\Response;
use Tinywan\Storage\Storage;//文件存储


class Controller
{
    /**
     */
    protected $model = null;

    /**
     * @title 获取列表
     * @method get
     * @return Response
     */
    function getDataList(Request $request){
        $request_data = $request->all();
        $request_data['page_size']=isset($request_data['page_size']) ?  $request_data['page_size'] : 20;
        $data=$this->model->getDataList($request_data);
        if(!$data) return self::json(['code'=>1,'msg'=>$this->model->msg]);
        return self::json(['code'=>0,'data'=>$data]);
    }
    /**
     * 更新
     * @param Request $request
     * @return Response
     */
    public function save(Request $request)
    {
        $id=$request->input('id');
        $data=$request->all();
        if(empty($data['id']))
        {
            $item=$this->model->setSave($data);
            if(!$item) return self::json(['code'=>1,'msg'=>$this->model->msg]);
        }
        else
        {
            $item=$this->model->find($id);
            if(!$item) return self::json(['code'=>1,'msg'=>$this->model->msg]);
            if(!$item->setSave($data)) return self::json(['code'=>1,'msg'=>$item->msg]);
        }
        return self::json(['code'=>0,'data'=>$item]);
    }


    /**
     * @title 获取表字段结构
     * @method get
     * @return Response
     */
    public function getTableInit(Request $request)
    {
        $is_update=$request->get('is_update',0);
        $data=$this->model->getTableInfo($is_update,$this->model);
        if(!$data) return self::json(['code'=>1,'msg'=>$this->model->msg]);
        $data['admin_path']=config('plugin.irmyang.database.app.admin_path');
        return self::json(['code'=>0,'data'=>$data]);
    }
    /**
     * @title 获取字段列表
     * @method get
     * @return Response
     */
    function getFieldList(Request $request){
        $model=$this->model->getTableFieldModel();
        $data=[];
        $data['lists']=$model->getFieldList($this->model);
        $data['field_type_lists']=$model->getValueTypeList('array');
        return self::json(['code'=>0,'data'=>$data]);
    }
    /**
     * 更新字段
     * @param Request $request
     * @return Response
     */
    public function changeFieldValue(Request $request)
    {
        $field=$request->post('field','');
        $value=$request->post($field,'');
        $id=$request->post('id',0);
        $model=$this->model->getTableFieldModel();
        $item=$model->find($id);
        if(!$item) return self::json(['code'=>1,'msg'=>'不存在']);
        $item->$field=$value;
        $item->save();
        $this->model->getTableInfo(true);
        return self::json(['code'=>0,'data'=>$item]);
    }
    /**
     * @title 排序移动
     * @method get
     * @param int id 内容ID
     * @param int [type] 移动类型
     */
    function addFieldValue(Request $request){
        $data = $request->all();
        $model=$this->model->getTableFieldModel();
        $table=$this->model->getTable();
        if(!$model->add($table,$data)) return self::json(['code'=>1,'msg'=>$model->msg]);
        $this->model->getTableInfo(true);
        return json(['code'=>0]);
    }
    /**
     * @title 排序移动
     * @method get
     * @param int id 内容ID
     * @param int [type] 移动类型
     */
    function moveFieldSort(Request $request){
        $id=$request->input('id',0);
        $type=$request->input('type',false);
        $model=$this->model->getTableFieldModel();
        $data=$model->moveSort($id,$type);
        if(!$data) return self::json(['code'=>1,'msg'=>$model->msg]);
        $this->model->getTableInfo(true);
        return self::json(['code'=>0,'data'=>$data]);
    }
    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function deleteFieldValue(Request $request)
    {
        $model=$this->model->getTableFieldModel();
        $id=$request->post('id',0);
        if(is_array($id))
        {
            $model=$model->whereIn('id',$id);
        }
        else
        {
            $model=$model->where('id',$id);
        }
        $lists=$model->get();
        foreach($lists as $v) $v->dropField($this->model);
        $this->model->getTableInfo(true);
        return self::json(['code' => 0, 'msg'=>'ok']);
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function getFieldModelField(Request $request)
    {
        $model=$request->input('model');
        $model=trim(urldecode($model));
        if(!class_exists($model))
        {
            return self::json(['code'=>1,'msg'=>'模块类不存在']);
        }
        else
        {
            $db=new $model;
            $data=$db->getTableInfo(true);
            return self::json(['code' => 0, 'data'=>$data]);
        }
    }

    /**@title 上传文件
     */
    public function fileUpload(Request $request){
        try {
            $res = Storage::uploadFile();
            return self::json(['code' => 0, 'data' => $res[0]]);
        } catch (\Exception $e) {
            return self::json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * @title 获取返回数据结构
     * @param array $data 参数
     * @param int $options 类型
     * @return Response
     */

    public static function json($data, int $options = JSON_UNESCAPED_UNICODE): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($data, $options));
    }
}
