<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

/**
 * 用户地址表
 */
class Address Extends Model
{
    use SoftDelete;
    // 表名
    protected $name = 'address';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 时间戳格式化
    protected $dateFormat = 'Y-m-d H:i:s';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';
    // 追加属性
    protected $append = [
        'is_default_text',
    ];

    public function getIsDefaultTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_default']) ? $data['is_default'] : '');
        return $value === '' ? '' : ((int)$value ? '默认' : '非默认');
    }

    public function user(){
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id');
    }
}
