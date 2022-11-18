<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\Category;
use app\admin\model\article\Article as Art;

/**
 * 文章接口
 */
class Article extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 文章分类
     * @ApiMethod (GET)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function category()
    {
        $category = Category::field(['id','name','image'])->where(['type' => 'article', 'status' => 'normal'])->order('weigh desc,id desc')->select();
        foreach ($category as &$value){
            if($value['image']) $value['image'] = cdnurl($value['image'], true);
        }
        if (!is_array($category)) {
            $this->error(__('Get data failed'));
        } else {
            $this->success(__('Get data success'), $category);
        }
    }

    /**
     * 文章列表
     * @ApiMethod (GET)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lst()
    {
        $cateId = $this->request->get('cate_id', 0);
        $where['status'] = 1;
        if($cateId) $where['category_id'] = $cateId;
        $list = Art::field(['id','title', 'content', 'image', 'createtime'])->where($where)->order('sort desc,id desc')->paginate(10)->toArray();
        foreach ($list['data'] as &$value){
            if($value['image']) $value['image'] = cdnurl($value['image'], true);
        }
        if (!is_array($list)) {
            $this->error(__('Get data failed'));
        } else {
            $this->success(__('Get data success'), $list);
        }
    }

    /**
     * 文章详情
     * @ApiMethod (GET)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail()
    {
        $id = $this->request->get('id', 0);
        if(!$id) $this->error('参数错误');
        $detail = Art::field(['id','title','image','content', 'createtime'])->find($id);
        if(!$detail) $this->error(__('No results were found'));
        $detail['image'] = cdnurl($detail['image'], true);
        //$detail['content'] = htmlentities($detail['content']);
        if (!$detail) {
            $this->error(__('Get data failed'));
        } else {
            $this->success(__('Get data success'), $detail);
        }
    }

}
