<?php

namespace app\admin\model\auction;

use think\Model;


class Order extends Model
{
    // 表名
    protected $name = 'auction_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';
    // 自动时间戳格式化
    protected $dateFormat = 'Y-m-d H:i:s';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_order']) ? $data['is_order'] : '');
        return (int)$value == 1 ? '待發貨' : '已發貨';
    }

    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
