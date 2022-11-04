<?php

namespace app\admin\model;

use think\Model;

class Category extends Model
{
    // 表名
    protected $name = 'category';


    public function goods()
    {
        return $this->hasMany('app\admin\model\auction\Goods', 'category_id', 'id');
    }
    
}
