<?php

namespace app\common\model;

use think\Model;

/**
 * 拍卖商品出价记录模型
 */
class GoodsPriceLog Extends Model
{

    // 表名
    protected $name = 'goods_price_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 时间戳格式化
    protected $dateFormat = 'Y-m-d H:i:s';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    // 追加属性
    protected $append = [
    ];

    public function user(){
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id')->bind('username');
    }
}
