<?php

namespace app\common\model;

use app\admin\model\auction\Goods;
use think\Model;

/**
 * 拍卖成交订单表
 */
class AuctionOrder Extends Model
{

    // 表名
    protected $name = 'auction_order';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 时间戳格式化
    protected $dateFormat = 'Y-m-d H:i:s';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
    ];

    public function user(){
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id')->bind('username');
    }

    public function goods(){
        return $this->belongsTo(Goods::class, 'goods_id', 'id')->bind('title');
    }
}
