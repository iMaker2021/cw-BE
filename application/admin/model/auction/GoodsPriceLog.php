<?php

namespace app\admin\model\auction;

use think\Model;


class GoodsPriceLog extends Model
{
    // 表名
    protected $name = 'goods_price_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $deleteTime = 'deletetime';

    protected $dateFormat = "Y-m-d H:i:s";

    // 追加属性
    protected $append = [

    ];
    

    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id');
    }
}
