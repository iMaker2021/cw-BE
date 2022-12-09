<?php

namespace app\admin\model\auction;

use think\Model;
use traits\model\SoftDelete;

class Goods extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'auction_goods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $dateFormat = 'Y-m-d H:i:s';

    private $isOrderText = ['進行中', '已成交', '流拍'];

    // 追加属性
    protected $append = [
        'begin_time_text',
        'end_time_text',
        'is_order_text',
        'status_text'
    ];


    /**
     * 订单状态转换
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getIsOrderTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_order']) ? $data['is_order'] : '');
        return $value === '' ? '' : (in_array($value, [0, 1, 2]) ? $this->isOrderText[$value] : '');
    }

    /**
     * 商品状态转换
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        return $value === '' ? '' : ((int)$value ? '正常' : '已作廢');
    }


    public function getBeginTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['begin_time']) ? $data['begin_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['end_time']) ? $data['end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setBeginTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEndTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function category()
    {
        return $this->belongsTo('app\admin\model\Category', 'category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function priceLog()
    {
        return $this->hasMany('app\common\model\GoodsPriceLog', 'goods_id', 'id');
    }
}
