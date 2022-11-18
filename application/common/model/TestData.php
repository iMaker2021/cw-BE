<?php

namespace app\common\model;

use think\Model;

/**
 * 会员积分日志模型
 */
class TestData Extends Model
{

    // 表名
    protected $name = 'test_data';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
    ];


    public function category()
    {
        return $this->belongsTo(\app\admin\model\Category::class, 'cate_id', 'id');
    }
}
