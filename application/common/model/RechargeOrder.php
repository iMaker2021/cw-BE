<?php

namespace app\common\model;

use think\Model;

/**
 * 用户充值订单模型
 */
class RechargeOrder Extends Model
{

    // 表名
    protected $name = 'recharge_order';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 时间戳格式化
    protected $dateFormat = 'Y-m-d H:i:s';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $payTime = 'paytime';
    // 追加属性
    protected $append = [
        'paytime_text' => '支付时间'
    ];

    public function user(){
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id')->bind('username');
    }
}
