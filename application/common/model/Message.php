<?php

namespace app\common\model;

use think\Db;
use think\Model;
use traits\model\SoftDelete;

/**
 * 消息模型
 */
class Message extends Model
{
    use SoftDelete;

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';
    //时间自动转换
    protected $dateFormat = 'Y-m-d H:i:s';
    // 追加属性
    protected $append = [
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->bind('username');
    }
}
