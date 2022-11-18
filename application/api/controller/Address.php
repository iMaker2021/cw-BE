<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Address as Addr;

/**
 * 地址接口
 */
class Address extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    protected $model;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model(Addr::class);
    }

    /**
     * 用户地址列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lst()
    {
        $list = $this->model->field('id,receive_name,phone,address,is_default')->where('user_id', $this->auth->id)->select();
        if($list === false){
            $this->error(__('Get data failed'));
        }
        $this->success(__('Get data success'), $list);
    }

    /**
     * 添加/修改地址
     */
    public function save()
    {
        $id = $this->request->param('id', 0);
        $receiveName = $this->request->param('receive_name', '');
        $phone = $this->request->param('phone', '');
        $address = $this->request->param('address', '');
        $isDefault = (int)$this->request->param('is_default', 0);
        //如果设置默认地址设置其他地址为非默认
        if($isDefault){
            $this->model->where('user_id', $this->auth->id)->update(['is_default' => 0]);
        }
        //id不为空则为修改，验证地址信息是否属于自己
        if($id){
            $this->model = $this->model->find($id);
            if($this->model->user_id != $this->auth->id) $this->error(__('Illegal operation'));
        }
        $this->model->receive_name = $receiveName;
        $this->model->phone = $phone;
        $this->model->address = $address;
        $this->model->is_default = $isDefault;
        $this->model->user_id = $this->auth->id;
        $result = $this->model->save();
        if($result === false){
            $this->error(__('Operate failed'));
        }
        $this->success('success');
    }

    /**
     * 设置默认地址
     */
    public function set_default()
    {
        $id = $this->request->param('id', 0);
        if(!$id) $this->error(__('Invalid parameters'));
        $this->model->where('user_id', $this->auth->id)->update(['is_default' => 0]);
        $result = $this->model->save(['is_default' => 1], ['id' => $id]);
        if($result === false) $this->error(__('Operate failed'));
        $this->success('success');
    }

    /**
     * 删除成功
     */
    public function del()
    {
        $param = $this->request->param();
        $ids = isset($param['ids']) ? $param['ids'] : [];
        if(!$ids || !is_array($ids)) $this->error(__('Invalid parameters'));
        $result = $this->model->where('user_id', $this->auth->id)->whereIn('id', $ids)->update(['deletetime' => time()]);
        if(!$result) $this->error(__('Operate failed'));
        $this->success('success');
    }
}
