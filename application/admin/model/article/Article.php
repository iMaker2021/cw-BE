<?php

namespace app\admin\model\article;

use think\Model;
use traits\model\SoftDelete;

class Article extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'article';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';
    // 自动格式化时间
    protected $dateFormat = 'Y-m-d H:i:s';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [

    ];
    

    







    public function category()
    {
        return $this->belongsTo('app\admin\model\Category', 'category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
